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
        $canChangeAssignment = $assignments->allows(
            request()->user(),
        );

        return view('shelter.cases.show', [
            'case' => $case->load(['petProfile', 'assignedTo']),
            'canChangeAssignment' => $canChangeAssignment,
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
        Gate::authorize('update', $case);
        $assignmentRequested = array_key_exists(
            'assigned_to',
            $request->all(),
        );
        if ($assignmentRequested) {
            $assignments->authorizeChange(
                $request,
                $request->user(),
                $case,
            );
        }

        $validated = $request->validate([
            'status' => ['required', 'in:open,in_review,closed'],
            'assigned_to' => [
                'sometimes',
                'nullable',
                'integer',
                new EligibleStaffAssignee,
            ],
            'details' => ['nullable', 'string'],
        ]);

        $updates = [
            'status' => $validated['status'],
            'details' => $validated['details'] ?? $case->details,
            'closed_at' => $validated['status'] === 'closed' ? now() : null,
        ];

        if ($assignmentRequested) {
            $updates['assigned_to'] = $validated['assigned_to'] ?? null;
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
