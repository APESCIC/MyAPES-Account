<?php

namespace App\Http\Controllers\Shelter;

use App\Http\Controllers\Controller;
use App\Models\PetProfile;
use App\Models\ShelterCase;
use App\Models\User;
use App\Notifications\ShelterCaseUpdatedNotification;
use App\Rules\EligibleStaffAssignee;
use App\Services\AssignmentAuthorization;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CaseController extends Controller
{
    public const CASE_TYPES = ['adoption', 'surrender', 'rescue', 'fostering'];

    public const STATUSES = ['open', 'in_review', 'closed'];

    public function index(): View
    {
        $user = request()->user();
        Gate::authorize('viewAny', ShelterCase::class);
        $query = ShelterCase::query()
            ->forSubCore(ShelterCase::SUB_CORE_SHELTER_RESCUE)
            ->visibleTo($user)
            ->whereHas(
                'petProfile',
                static fn ($pets) => $pets->where(
                    'service_domain',
                    PetProfile::DOMAIN_SHELTER,
                ),
            )
            ->with(['petProfile', 'assignedTo'])
            ->latest();

        return view('shelter.cases.index', [
            'cases' => $query->paginate(20),
            'canCreateCase' => $user->can(
                ShelterCase::SUB_CORE_SHELTER_RESCUE.'.cases.create',
            ),
            'petProfiles' => PetProfile::query()
                ->where('service_domain', PetProfile::DOMAIN_SHELTER)
                ->visibleTo($user, PetProfile::DOMAIN_SHELTER)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        Gate::authorize('create', ShelterCase::class);
        $selectedPet = $request->validate([
            'pet_profile_id' => ['required', 'integer'],
        ]);
        $pet = PetProfile::query()
            ->where('service_domain', PetProfile::DOMAIN_SHELTER)
            ->visibleTo($request->user(), PetProfile::DOMAIN_SHELTER)
            ->findOrFail($selectedPet['pet_profile_id']);
        Gate::authorize('view', $pet);
        Gate::authorize('createShelterCase', $pet);

        $validated = $request->validate([
            'pet_profile_id' => ['required', 'integer'],
            'case_type' => ['required', 'in:adoption,surrender,rescue,fostering'],
            'title' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string'],
        ]);

        $case = ShelterCase::create([
            ...$validated,
            'sub_core_key' => ShelterCase::SUB_CORE_SHELTER_RESCUE,
            'user_id' => $pet->user_id,
            'status' => 'open',
        ]);

        $this->notifyCaseStakeholders($case, $request->user(), 'created');
        $auditLogger->record('shelter.case.created', $request->user(), $case, [
            'case_type' => $case->case_type,
            'status' => $case->status,
        ]);

        return redirect()->route('shelter.cases.show', $case);
    }

    public function show(
        ShelterCase $case,
        AssignmentAuthorization $assignments,
    ): View {
        $case = $this->visibleShelterCase($case, request()->user());
        Gate::authorize('view', $case);
        $user = request()->user();
        $prefix = ShelterCase::SUB_CORE_SHELTER_RESCUE.'.cases.';
        $canViewAll = $user->can($prefix.'view-all');
        $canChangeAssignment = $assignments->allows($user)
            && $user->can($prefix.'assign');
        $updates = $case->updates()->with('user')->oldest();
        if (! $canViewAll) {
            $updates->where('visibility', 'public');
        }

        return view('shelter.cases.show', [
            'case' => $case->load(['petProfile', 'assignedTo']),
            'updates' => $updates->get(),
            'caseTypes' => self::CASE_TYPES,
            'statuses' => self::STATUSES,
            'canChangeAssignment' => $canChangeAssignment,
            'canUpdateCase' => $user->can($prefix.'update-all')
                || ($case->user_id === $user->id
                    && $user->can($prefix.'update-own')),
            'canCloseCase' => $user->can($prefix.'close'),
            'canCommentCase' => $user->can($prefix.'comment-own')
                && $case->status !== 'closed',
            'canChooseVisibility' => $canViewAll,
            'staffUsers' => $canChangeAssignment
                ? User::query()
                    ->eligibleStaff()
                    ->withAuthorizationPermission($prefix.'view-all')
                    ->orderBy('name')
                    ->get()
                : collect(),
        ]);
    }

    public function update(
        Request $request,
        ShelterCase $case,
        AuditLogger $auditLogger,
        AssignmentAuthorization $assignments,
    ): RedirectResponse {
        $case = $this->visibleShelterCase($case, $request->user());
        Gate::authorize('view', $case);
        $metadataFields = ['case_type', 'title', 'details', 'status'];
        $metadataRequested = collect($metadataFields)
            ->contains(fn (string $field): bool => $request->exists($field));
        $assignmentRequested = $request->exists('assigned_to');
        if (! $metadataRequested && ! $assignmentRequested) {
            throw ValidationException::withMessages([
                'case' => 'Select a case change before submitting.',
            ]);
        }

        $prefix = ShelterCase::SUB_CORE_SHELTER_RESCUE.'.cases.';
        $otherMetadataRequested = collect([
            'case_type',
            'title',
            'details',
        ])->contains(fn (string $field): bool => $request->exists($field));
        $closedBoundaryRequested = $request->exists('status')
            && $request->input('status') !== $case->status
            && ($case->status === 'closed'
                || $request->input('status') === 'closed');

        if ($metadataRequested
            && ($otherMetadataRequested || ! $closedBoundaryRequested)) {
            Gate::authorize('update', $case);
        }
        if ($closedBoundaryRequested) {
            Gate::authorize($prefix.'close');
        }
        if ($assignmentRequested) {
            $assignments->authorizeChange($request, $request->user(), $case);
            Gate::authorize($prefix.'assign');
        }

        $rules = [];
        if ($request->exists('case_type')) {
            $rules['case_type'] = ['required', Rule::in(self::CASE_TYPES)];
        }
        if ($request->exists('title')) {
            $rules['title'] = ['required', 'string', 'max:255'];
        }
        if ($request->exists('details')) {
            $rules['details'] = ['nullable', 'string'];
        }
        if ($request->exists('status')) {
            $rules['status'] = ['required', Rule::in(self::STATUSES)];
        }
        if ($assignmentRequested) {
            $rules['assigned_to'] = [
                'sometimes',
                'nullable',
                'integer',
                new EligibleStaffAssignee(
                    ShelterCase::SUB_CORE_SHELTER_RESCUE.'.cases.view-all',
                ),
            ];
        }
        $validated = $request->validate($rules);

        $updates = collect($metadataFields)
            ->filter(fn (string $field): bool => array_key_exists($field, $validated))
            ->mapWithKeys(fn (string $field): array => [
                $field => $validated[$field],
            ])
            ->all();
        if ($assignmentRequested) {
            $updates['assigned_to'] = isset($validated['assigned_to'])
                ? (int) $validated['assigned_to']
                : null;
        }

        $statusChanged = array_key_exists('status', $updates)
            && $updates['status'] !== $case->status;
        if ($statusChanged && ($case->status === 'closed'
            || $updates['status'] === 'closed')) {
            Gate::authorize($prefix.'close');
        }
        if ($statusChanged) {
            $updates['closed_at'] = $updates['status'] === 'closed'
                ? now()
                : null;
        }

        $case->fill($updates);
        if (! $case->isDirty()) {
            throw ValidationException::withMessages([
                'case' => 'Select a case change before submitting.',
            ]);
        }
        $case->save();

        $this->notifyCaseStakeholders($case, $request->user(), 'updated');
        $auditLogger->record('shelter.case.updated', $request->user(), $case, [
            'sub_core_key' => $case->sub_core_key,
            'module_key' => 'cases',
            'status' => $case->status,
            'assigned_to' => $case->assigned_to,
        ]);

        return redirect()->route('shelter.cases.show', $case)->with('status', 'Case updated.');
    }

    private function visibleShelterCase(
        ShelterCase $case,
        User $user,
    ): ShelterCase {
        return ShelterCase::query()
            ->forSubCore(ShelterCase::SUB_CORE_SHELTER_RESCUE)
            ->visibleTo($user, ShelterCase::SUB_CORE_SHELTER_RESCUE)
            ->whereKey($case->getKey())
            ->whereHas(
                'petProfile',
                static fn ($pets) => $pets->where(
                    'service_domain',
                    PetProfile::DOMAIN_SHELTER,
                ),
            )
            ->firstOrFail();
    }

    private function notifyCaseStakeholders(ShelterCase $case, User $actor, string $eventLabel): void
    {
        $prefix = ShelterCase::SUB_CORE_SHELTER_RESCUE.'.cases.';
        $staffRecipients = User::query()
            ->eligibleStaff()
            ->withAuthorizationPermission($prefix.'view-all')
            ->get();

        if ($case->user?->can('view', $case)) {
            $staffRecipients->push($case->user);
        }

        $recipients = $staffRecipients
            ->unique('id')
            ->reject(fn (User $recipient): bool => $recipient->id === $actor->id);

        foreach ($recipients as $recipient) {
            $recipient->notify(new ShelterCaseUpdatedNotification($case, $actor, $eventLabel));
        }
    }
}
