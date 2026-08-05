<?php

namespace App\Http\Controllers\PetCare;

use App\Http\Controllers\Controller;
use App\Models\PetCareConsultation;
use App\Models\PetProfile;
use App\Models\User;
use App\Notifications\ConsultationUpdatedNotification;
use App\Rules\EligibleStaffAssignee;
use App\Services\AssignmentAuthorization;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ConsultationController extends Controller
{
    public function index(): View
    {
        $user = request()->user();
        Gate::authorize('viewAny', PetCareConsultation::class);
        $query = PetCareConsultation::query()
            ->visibleTo($user)
            ->with(['petProfile', 'assignedTo'])
            ->latest();

        return view('petcare.consultations.index', [
            'consultations' => $query->paginate(20),
            'petProfiles' => PetProfile::query()
                ->where('service_domain', PetProfile::DOMAIN_PETCARE)
                ->visibleTo($user)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'pet_profile_id' => ['required', 'exists:pet_profiles,id'],
            'subject' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'scheduled_for' => ['nullable', 'date'],
        ]);

        $pet = PetProfile::query()->findOrFail($validated['pet_profile_id']);
        Gate::authorize('createConsultation', $pet);

        $consultation = PetCareConsultation::create([
            ...$validated,
            'user_id' => $pet->user_id,
        ]);

        $this->notifyConsultationStakeholders($consultation, $request->user(), 'created');
        $auditLogger->record('petcare.consultation.created', $request->user(), $consultation, [
            'status' => $consultation->status,
            'scheduled_for' => $consultation->scheduled_for,
        ]);

        return redirect()->route('petcare.consultations.show', $consultation);
    }

    public function show(
        PetCareConsultation $consultation,
        AssignmentAuthorization $assignments,
    ): View {
        Gate::authorize('view', $consultation);
        $canChangeAssignment = $assignments->allows(
            request()->user(),
        );

        return view('petcare.consultations.show', [
            'consultation' => $consultation->load(['petProfile', 'assignedTo']),
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
        PetCareConsultation $consultation,
        AuditLogger $auditLogger,
        AssignmentAuthorization $assignments,
    ): RedirectResponse {
        Gate::authorize('update', $consultation);
        $assignmentRequested = array_key_exists(
            'assigned_to',
            $request->all(),
        );
        if ($assignmentRequested) {
            $assignments->authorizeChange(
                $request,
                $request->user(),
                $consultation,
            );
        }

        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,closed'],
            'assigned_to' => [
                'sometimes',
                'nullable',
                'integer',
                new EligibleStaffAssignee,
            ],
            'notes' => ['nullable', 'string'],
            'scheduled_for' => ['nullable', 'date'],
        ]);

        $updates = [
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? $consultation->notes,
            'scheduled_for' => $validated['scheduled_for'] ?? $consultation->scheduled_for,
            'closed_at' => $validated['status'] === 'closed' ? now() : null,
        ];

        if ($assignmentRequested) {
            $updates['assigned_to'] = $validated['assigned_to'] ?? null;
        }

        $consultation->update($updates);

        $this->notifyConsultationStakeholders($consultation, $request->user(), 'updated');
        $auditLogger->record('petcare.consultation.updated', $request->user(), $consultation, [
            'status' => $consultation->status,
            'assigned_to' => $consultation->assigned_to,
            'scheduled_for' => $consultation->scheduled_for,
        ]);

        return redirect()->route('petcare.consultations.show', $consultation)->with('status', 'Consultation updated.');
    }

    private function notifyConsultationStakeholders(PetCareConsultation $consultation, User $actor, string $eventLabel): void
    {
        $staffRecipients = User::query()
            ->eligibleStaff()
            ->get();

        $recipients = $staffRecipients
            ->push($consultation->user)
            ->unique('id')
            ->reject(fn (User $recipient): bool => $recipient->id === $actor->id);

        foreach ($recipients as $recipient) {
            $recipient->notify(new ConsultationUpdatedNotification($consultation, $actor, $eventLabel));
        }
    }
}
