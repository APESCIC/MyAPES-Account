<?php

namespace App\Http\Controllers\PetCare;

use App\Http\Controllers\Controller;
use App\Models\PetCareConsultation;
use App\Models\PetProfile;
use App\Models\User;
use App\Notifications\ConsultationUpdatedNotification;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    public function index(): View
    {
        $user = request()->user();
        $query = PetCareConsultation::query()->with(['petProfile', 'assignedTo'])->latest();

        if (! $user->isStaff()) {
            $query->where('user_id', $user->id);
        }

        return view('petcare.consultations.index', [
            'consultations' => $query->paginate(20),
            'petProfiles' => PetProfile::query()
                ->where('service_domain', PetProfile::DOMAIN_PETCARE)
                ->when(! $user->isStaff(), fn ($q) => $q->where('user_id', $user->id))
                ->orderBy('name')
                ->get(),
            'staffUsers' => User::query()
                ->whereIn('role', [User::ROLE_STAFF, User::ROLE_ADMIN, User::ROLE_SUPERADMIN])
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
        $this->authorizePet($pet);

        $consultation = PetCareConsultation::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        $this->notifyConsultationStakeholders($consultation, $request->user(), 'created');
        $auditLogger->record('petcare.consultation.created', $request->user(), $consultation, [
            'status' => $consultation->status,
            'scheduled_for' => $consultation->scheduled_for,
        ]);

        return redirect()->route('petcare.consultations.show', $consultation);
    }

    public function show(PetCareConsultation $consultation): View
    {
        $this->authorizeConsultation($consultation);

        return view('petcare.consultations.show', [
            'consultation' => $consultation->load(['petProfile', 'assignedTo']),
            'staffUsers' => User::query()
                ->whereIn('role', [User::ROLE_STAFF, User::ROLE_ADMIN, User::ROLE_SUPERADMIN])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, PetCareConsultation $consultation, AuditLogger $auditLogger): RedirectResponse
    {
        $this->authorizeConsultation($consultation);

        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,closed'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
            'scheduled_for' => ['nullable', 'date'],
        ]);

        if (! $request->user()->isStaff()) {
            $validated['assigned_to'] = null;
        }

        $consultation->update([
            'status' => $validated['status'],
            'assigned_to' => $validated['assigned_to'] ?? null,
            'notes' => $validated['notes'] ?? $consultation->notes,
            'scheduled_for' => $validated['scheduled_for'] ?? $consultation->scheduled_for,
            'closed_at' => $validated['status'] === 'closed' ? now() : null,
        ]);

        $this->notifyConsultationStakeholders($consultation, $request->user(), 'updated');
        $auditLogger->record('petcare.consultation.updated', $request->user(), $consultation, [
            'status' => $consultation->status,
            'assigned_to' => $consultation->assigned_to,
            'scheduled_for' => $consultation->scheduled_for,
        ]);

        return redirect()->route('petcare.consultations.show', $consultation)->with('status', 'Consultation updated.');
    }

    private function authorizeConsultation(PetCareConsultation $consultation): void
    {
        $user = request()->user();

        if ($user->isStaff()) {
            return;
        }

        if ($consultation->user_id !== $user->id) {
            abort(403);
        }
    }

    private function authorizePet(PetProfile $pet): void
    {
        $user = request()->user();

        if ($pet->service_domain !== PetProfile::DOMAIN_PETCARE) {
            abort(403);
        }

        if ($user->isStaff()) {
            return;
        }

        if ($pet->user_id !== $user->id) {
            abort(403);
        }
    }

    private function notifyConsultationStakeholders(PetCareConsultation $consultation, User $actor, string $eventLabel): void
    {
        $staffRecipients = User::query()
            ->whereIn('role', [User::ROLE_STAFF, User::ROLE_ADMIN, User::ROLE_SUPERADMIN])
            ->get();

        $recipients = $staffRecipients
            ->push($consultation->user)
            ->when($consultation->assignedTo !== null, fn ($collection) => $collection->push($consultation->assignedTo))
            ->unique('id')
            ->reject(fn (User $recipient): bool => $recipient->id === $actor->id);

        foreach ($recipients as $recipient) {
            $recipient->notify(new ConsultationUpdatedNotification($consultation, $actor, $eventLabel));
        }
    }
}
