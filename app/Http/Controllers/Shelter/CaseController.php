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
use Illuminate\Validation\ValidationException;

class CaseController extends Controller
{
    public function index(): View
    {
        $user = request()->user();
        Gate::authorize('viewAny', ShelterCase::class);
        $query = ShelterCase::query()
            ->forSubCore(ShelterCase::SUB_CORE_SHELTER_RESCUE)
            ->visibleTo($user)
            ->with(['petProfile', 'assignedTo'])
            ->latest();

        return view('shelter.cases.index', [
            'cases' => $query->paginate(20),
            'petProfiles' => PetProfile::query()
                ->where('service_domain', PetProfile::DOMAIN_SHELTER)
                ->visibleTo($user)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'pet_profile_id' => ['required', 'exists:pet_profiles,id'],
            'case_type' => ['required', 'in:adoption,surrender,rescue,fostering'],
            'title' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string'],
        ]);

        $pet = PetProfile::query()->findOrFail($validated['pet_profile_id']);
        Gate::authorize('createShelterCase', $pet);

        $case = ShelterCase::create([
            ...$validated,
            'sub_core_key' => ShelterCase::SUB_CORE_SHELTER_RESCUE,
            'user_id' => $pet->user_id,
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
        $this->requireShelterCase($case);
        Gate::authorize('view', $case);
        $user = request()->user();
        $prefix = ShelterCase::SUB_CORE_SHELTER_RESCUE.'.cases.';
        $canChangeAssignment = $assignments->allows($user)
            && $user->can($prefix.'assign');

        return view('shelter.cases.show', [
            'case' => $case->load(['petProfile', 'assignedTo']),
            'canChangeAssignment' => $canChangeAssignment,
            'canUpdateCase' => $user->can($prefix.'update-all')
                || ($case->user_id === $user->id
                    && $user->can($prefix.'update-own')),
            'canCloseCase' => $user->can($prefix.'close'),
            'staffUsers' => $canChangeAssignment
                ? User::query()
                    ->eligibleStaff()
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
        $this->requireShelterCase($case);
        Gate::authorize('view', $case);
        $input = $request->all();
        $statusRequested = array_key_exists('status', $input);
        $detailsRequested = array_key_exists('details', $input);
        $assignmentRequested = array_key_exists('assigned_to', $input);
        if (! $statusRequested && ! $detailsRequested && ! $assignmentRequested) {
            throw ValidationException::withMessages([
                'case' => 'Select a case change before submitting.',
            ]);
        }

        $rules = [];
        if ($statusRequested) {
            $rules['status'] = ['required', 'in:open,in_review,closed'];
        }
        if ($detailsRequested) {
            $rules['details'] = ['nullable', 'string'];
        }
        $validated = $request->validate($rules);

        $statusChanged = $statusRequested
            && $validated['status'] !== $case->status;
        $detailsChanged = $detailsRequested
            && $validated['details'] !== $case->details;
        $requestedAssignee = $case->assigned_to;
        if ($assignmentRequested) {
            $submittedAssignee = $input['assigned_to'];
            $requestedAssignee = $submittedAssignee === null
                || $submittedAssignee === ''
                ? null
                : (is_int($submittedAssignee)
                    || (is_string($submittedAssignee)
                        && ctype_digit($submittedAssignee))
                    ? (int) $submittedAssignee
                    : PHP_INT_MIN);
        }
        $assignmentChanged = $assignmentRequested
            && $requestedAssignee !== $case->assigned_to;

        if (! $statusChanged && ! $detailsChanged && ! $assignmentChanged) {
            throw ValidationException::withMessages([
                'case' => 'Select a case change before submitting.',
            ]);
        }

        $prefix = ShelterCase::SUB_CORE_SHELTER_RESCUE.'.cases.';
        if ($detailsChanged || ($statusChanged
            && $case->status !== 'closed'
            && $validated['status'] !== 'closed')) {
            Gate::authorize('update', $case);
        }
        if ($statusChanged && ($case->status === 'closed'
            || $validated['status'] === 'closed')) {
            Gate::authorize($prefix.'close');
        }
        if ($assignmentChanged) {
            $assignments->authorizeChange($request, $request->user(), $case);
            Gate::authorize($prefix.'assign');
            $assignmentValidated = $request->validate([
                'assigned_to' => [
                    'sometimes',
                    'nullable',
                    'integer',
                    new EligibleStaffAssignee,
                ],
            ]);
            $requestedAssignee = isset($assignmentValidated['assigned_to'])
                ? (int) $assignmentValidated['assigned_to']
                : null;
        }

        $updates = [];
        if ($statusChanged) {
            $updates['status'] = $validated['status'];
            $updates['closed_at'] = $validated['status'] === 'closed' ? now() : null;
        }
        if ($detailsChanged) {
            $updates['details'] = $validated['details'];
        }
        if ($assignmentChanged) {
            $updates['assigned_to'] = $requestedAssignee;
        }

        $case->update($updates);

        $this->notifyCaseStakeholders($case, $request->user(), 'updated');
        $auditLogger->record('shelter.case.updated', $request->user(), $case, [
            'status' => $case->status,
            'assigned_to' => $case->assigned_to,
        ]);

        return redirect()->route('shelter.cases.show', $case)->with('status', 'Case updated.');
    }

    private function requireShelterCase(ShelterCase $case): void
    {
        abort_unless(
            $case->sub_core_key === ShelterCase::SUB_CORE_SHELTER_RESCUE,
            404,
        );
    }

    private function notifyCaseStakeholders(ShelterCase $case, User $actor, string $eventLabel): void
    {
        $staffRecipients = User::query()
            ->eligibleStaff()
            ->get();

        $recipients = $staffRecipients
            ->push($case->user)
            ->unique('id')
            ->reject(fn (User $recipient): bool => $recipient->id === $actor->id);

        foreach ($recipients as $recipient) {
            $recipient->notify(new ShelterCaseUpdatedNotification($case, $actor, $eventLabel));
        }
    }
}
