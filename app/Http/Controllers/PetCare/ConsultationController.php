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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Throwable;

class ConsultationController extends Controller
{
    public function index(): View
    {
        $user = request()->user();
        Gate::authorize('viewAny', PetCareConsultation::class);
        $query = PetCareConsultation::query()
            ->forPetCareDomain()
            ->visibleTo($user)
            ->with(['petProfile', 'assignedTo'])
            ->latest();

        return view('petcare.consultations.index', [
            'consultations' => $query->paginate(20),
            'canCreate' => Gate::allows('create', PetCareConsultation::class),
            'petProfiles' => PetProfile::query()
                ->where('service_domain', PetProfile::DOMAIN_PETCARE)
                ->visibleTo($user, PetProfile::DOMAIN_PETCARE)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        Gate::authorize('create', PetCareConsultation::class);

        $validated = $request->validate([
            'pet_profile_id' => ['required', 'integer'],
            'subject' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'scheduled_for' => ['nullable', 'date'],
        ]);

        $pet = PetProfile::query()
            ->where('service_domain', PetProfile::DOMAIN_PETCARE)
            ->visibleTo($request->user(), PetProfile::DOMAIN_PETCARE)
            ->findOrFail($validated['pet_profile_id']);

        $consultation = PetCareConsultation::create([
            ...$validated,
            'user_id' => $pet->user_id,
        ]);

        $this->notifyConsultationStakeholders($consultation, $request->user(), 'created');
        $auditLogger->record('petcare.consultation.created', $request->user(), $consultation, [
            'status' => $consultation->status,
            'scheduled_for' => $consultation->scheduled_for,
            'sub_core_key' => 'pet-care-clinic',
            'module_key' => 'consultations',
        ]);

        return redirect()->route('petcare.consultations.show', $consultation);
    }

    public function show(
        PetCareConsultation $consultation,
    ): View {
        $consultation = $this->petCareConsultation($consultation);
        Gate::authorize('view', $consultation);
        $canUpdate = Gate::allows('update', $consultation);
        $canAssign = Gate::allows('assign', $consultation);
        $canClose = Gate::allows('close', $consultation);
        $consultation->load(['petProfile', 'assignedTo']);
        $eligibleStaff = User::query()
            ->eligibleStaff()
            ->withAuthorizationPermission(
                PetCareConsultation::PERMISSION_VIEW_ALL,
            );
        $currentAssigneeUnavailable = $consultation->assigned_to !== null
            && ! (clone $eligibleStaff)
                ->whereKey($consultation->assigned_to)
                ->exists();

        return view('petcare.consultations.show', [
            'consultation' => $consultation,
            'canUpdate' => $canUpdate,
            'canAssign' => $canAssign,
            'canClose' => $canClose,
            'currentAssigneeUnavailable' => $currentAssigneeUnavailable,
            'staffUsers' => $canAssign
                ? $eligibleStaff
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
        $consultation = $this->petCareConsultation($consultation);
        Gate::authorize('view', $consultation);
        if (array_key_exists('assigned_to', $request->all())) {
            $assignments->authorizeChange(
                $request,
                $request->user(),
                $consultation,
                PetCareConsultation::PERMISSION_ASSIGN,
            );
        }
        $changes = $this->requestedChanges($request, $consultation);
        if (! in_array(true, $changes, true)) {
            throw ValidationException::withMessages([
                'consultation' => 'No consultation changes were requested.',
            ]);
        }

        if ($changes['ordinary']) {
            Gate::authorize('update', $consultation);
        }
        if ($changes['terminal']) {
            Gate::authorize('close', $consultation);
        }

        $validated = $request->validate([
            'status' => ['sometimes', 'required', 'in:open,in_progress,closed'],
            'assigned_to' => [
                'sometimes',
                'nullable',
                'integer',
                new EligibleStaffAssignee(
                    PetCareConsultation::PERMISSION_VIEW_ALL,
                ),
            ],
            'notes' => ['sometimes', 'nullable', 'string'],
            'scheduled_for' => ['sometimes', 'nullable', 'date'],
        ]);

        $updates = [];
        foreach (['notes', 'scheduled_for'] as $field) {
            if ($changes[$field]) {
                $updates[$field] = $validated[$field];
            }
        }

        if ($changes['assignment']) {
            $updates['assigned_to'] = $validated['assigned_to'];
        }
        if ($changes['status']) {
            $updates['status'] = $validated['status'];
            if ($consultation->status !== 'closed'
                && $validated['status'] === 'closed') {
                $updates['closed_at'] = now();
            } elseif ($consultation->status === 'closed'
                && $validated['status'] !== 'closed') {
                $updates['closed_at'] = null;
            }
        }

        $consultation->fill($updates);
        $consultation->save();

        $this->notifyConsultationStakeholders($consultation, $request->user(), 'updated');
        $auditLogger->record('petcare.consultation.updated', $request->user(), $consultation, [
            'status' => $consultation->status,
            'assigned_to' => $consultation->assigned_to,
            'scheduled_for' => $consultation->scheduled_for,
            'sub_core_key' => 'pet-care-clinic',
            'module_key' => 'consultations',
        ]);

        return redirect()->route('petcare.consultations.show', $consultation)->with('status', 'Consultation updated.');
    }

    private function notifyConsultationStakeholders(PetCareConsultation $consultation, User $actor, string $eventLabel): void
    {
        $staffRecipients = User::query()
            ->eligibleStaff()
            ->withAuthorizationPermission(
                PetCareConsultation::PERMISSION_VIEW_ALL,
            )
            ->get();

        $owner = $consultation->user;
        $recipients = $owner !== null
            && Gate::forUser($owner)->allows('view', $consultation)
            ? $staffRecipients->push($owner)
            : $staffRecipients;
        $recipients = $recipients
            ->unique('id')
            ->reject(fn (User $recipient): bool => $recipient->id === $actor->id);

        foreach ($recipients as $recipient) {
            $recipient->notify(new ConsultationUpdatedNotification($consultation, $actor, $eventLabel));
        }
    }

    private function petCareConsultation(
        PetCareConsultation $consultation,
    ): PetCareConsultation {
        return PetCareConsultation::query()
            ->forPetCareDomain()
            ->findOrFail($consultation->getKey());
    }

    /** @return array{ordinary: bool, assignment: bool, terminal: bool, status: bool, notes: bool, scheduled_for: bool} */
    private function requestedChanges(
        Request $request,
        PetCareConsultation $consultation,
    ): array {
        $input = $request->all();
        $notes = array_key_exists('notes', $input)
            && $input['notes'] !== $consultation->notes;
        $scheduled = array_key_exists('scheduled_for', $input)
            && ! $this->sameSchedule(
                $input['scheduled_for'],
                $consultation->scheduled_for,
            );
        $assignment = array_key_exists('assigned_to', $input)
            && ! $this->sameIdentifier(
                $input['assigned_to'],
                $consultation->assigned_to,
            );
        $status = array_key_exists('status', $input)
            && $input['status'] !== $consultation->status;
        $terminal = $status
            && ($consultation->status === 'closed'
                || $input['status'] === 'closed');
        $ordinaryStatus = $status && ! $terminal;

        return [
            'ordinary' => $notes || $scheduled || $ordinaryStatus,
            'assignment' => $assignment,
            'terminal' => $terminal,
            'status' => $status,
            'notes' => $notes,
            'scheduled_for' => $scheduled,
        ];
    }

    private function sameIdentifier(mixed $requested, ?int $current): bool
    {
        if ($requested === null || $requested === '') {
            return $current === null;
        }

        return filter_var($requested, FILTER_VALIDATE_INT) !== false
            && (int) $requested === $current;
    }

    private function sameSchedule(mixed $requested, ?Carbon $current): bool
    {
        if ($requested === null || $requested === '') {
            return $current === null;
        }
        if (! is_string($requested)) {
            return false;
        }

        try {
            return $current !== null
                && Carbon::parse($requested)->equalTo($current);
        } catch (Throwable) {
            return false;
        }
    }
}
