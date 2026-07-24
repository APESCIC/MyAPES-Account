<?php

namespace App\Http\Controllers\Shelter;

use App\Http\Controllers\Controller;
use App\Models\PetProfile;
use App\Models\ShelterCase;
use App\Models\User;
use App\Notifications\ShelterCaseUpdatedNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CaseController extends Controller
{
    public function index(): View
    {
        $user = request()->user();
        $query = ShelterCase::query()->with(['petProfile', 'assignedTo'])->latest();

        if (! $user->isStaff()) {
            $query->where('user_id', $user->id);
        }

        return view('shelter.cases.index', [
            'cases' => $query->paginate(20),
            'petProfiles' => PetProfile::query()
                ->where('service_domain', PetProfile::DOMAIN_SHELTER)
                ->when(! $user->isStaff(), fn ($q) => $q->where('user_id', $user->id))
                ->orderBy('name')
                ->get(),
            'staffUsers' => User::query()
                ->whereIn('role', [User::ROLE_STAFF, User::ROLE_ADMIN, User::ROLE_SUPERADMIN])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pet_profile_id' => ['required', 'exists:pet_profiles,id'],
            'case_type' => ['required', 'in:adoption,surrender,rescue,fostering'],
            'title' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string'],
        ]);

        $pet = PetProfile::query()->findOrFail($validated['pet_profile_id']);
        $this->authorizePet($pet);

        $case = ShelterCase::create([
            ...$validated,
            'user_id' => $request->user()->id,
        ]);

        $this->notifyCaseStakeholders($case, $request->user(), 'created');

        return redirect()->route('shelter.cases.show', $case);
    }

    public function show(ShelterCase $case): View
    {
        $this->authorizeCase($case);

        return view('shelter.cases.show', [
            'case' => $case->load(['petProfile', 'assignedTo']),
            'staffUsers' => User::query()
                ->whereIn('role', [User::ROLE_STAFF, User::ROLE_ADMIN, User::ROLE_SUPERADMIN])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, ShelterCase $case): RedirectResponse
    {
        $this->authorizeCase($case);

        $validated = $request->validate([
            'status' => ['required', 'in:open,in_review,closed'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'details' => ['nullable', 'string'],
        ]);

        if (! $request->user()->isStaff()) {
            $validated['assigned_to'] = null;
        }

        $case->update([
            'status' => $validated['status'],
            'assigned_to' => $validated['assigned_to'] ?? null,
            'details' => $validated['details'] ?? $case->details,
            'closed_at' => $validated['status'] === 'closed' ? now() : null,
        ]);

        $this->notifyCaseStakeholders($case, $request->user(), 'updated');

        return redirect()->route('shelter.cases.show', $case)->with('status', 'Case updated.');
    }

    private function authorizeCase(ShelterCase $case): void
    {
        $user = request()->user();

        if ($user->isStaff()) {
            return;
        }

        if ($case->user_id !== $user->id) {
            abort(403);
        }
    }

    private function authorizePet(PetProfile $pet): void
    {
        $user = request()->user();

        if ($pet->service_domain !== PetProfile::DOMAIN_SHELTER) {
            abort(403);
        }

        if ($user->isStaff()) {
            return;
        }

        if ($pet->user_id !== $user->id) {
            abort(403);
        }
    }

    private function notifyCaseStakeholders(ShelterCase $case, User $actor, string $eventLabel): void
    {
        $staffRecipients = User::query()
            ->whereIn('role', [User::ROLE_STAFF, User::ROLE_ADMIN, User::ROLE_SUPERADMIN])
            ->get();

        $recipients = $staffRecipients
            ->push($case->user)
            ->when($case->assignedTo !== null, fn ($collection) => $collection->push($case->assignedTo))
            ->unique('id')
            ->reject(fn (User $recipient): bool => $recipient->id === $actor->id);

        foreach ($recipients as $recipient) {
            $recipient->notify(new ShelterCaseUpdatedNotification($case, $actor, $eventLabel));
        }
    }
}
