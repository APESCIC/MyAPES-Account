<?php

namespace App\Http\Controllers\PetCare;

use App\Http\Controllers\Controller;
use App\Models\PetProfile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PetProfileController extends Controller
{
    public function index(): View
    {
        $user = request()->user();
        $query = PetProfile::query()->where('service_domain', PetProfile::DOMAIN_PETCARE)->latest();

        if (! $user->isStaff()) {
            $query->where('user_id', $user->id);
        }

        return view('petcare.pets.index', [
            'pets' => $query->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'species' => ['nullable', 'string', 'max:255'],
            'age_years' => ['nullable', 'integer', 'between:0,80'],
            'sex' => ['required', 'in:male,female,unknown'],
            'neutering_status' => ['required', 'in:neutered,not_neutered,unknown'],
            'health_issues' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:3072'],
        ]);

        $pet = new PetProfile([
            ...$validated,
            'service_domain' => PetProfile::DOMAIN_PETCARE,
            'user_id' => $request->user()->id,
        ]);

        if ($request->hasFile('photo')) {
            $pet->photo_path = $request->file('photo')->store('pet-profiles', 'public');
        }

        $pet->save();

        return redirect()->route('petcare.pets.show', $pet);
    }

    public function show(PetProfile $pet): View
    {
        $this->authorizePet($pet, PetProfile::DOMAIN_PETCARE);

        return view('petcare.pets.show', ['pet' => $pet]);
    }

    public function update(Request $request, PetProfile $pet): RedirectResponse
    {
        $this->authorizePet($pet, PetProfile::DOMAIN_PETCARE);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'species' => ['nullable', 'string', 'max:255'],
            'age_years' => ['nullable', 'integer', 'between:0,80'],
            'sex' => ['required', 'in:male,female,unknown'],
            'neutering_status' => ['required', 'in:neutered,not_neutered,unknown'],
            'health_issues' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'max:3072'],
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('pet-profiles', 'public');
        }

        $pet->update($validated);

        return redirect()->route('petcare.pets.show', $pet)->with('status', 'Pet profile updated.');
    }

    private function authorizePet(PetProfile $pet, string $domain): void
    {
        $user = request()->user();

        if ($pet->service_domain !== $domain) {
            abort(404);
        }

        if ($user->isStaff()) {
            return;
        }

        if ($pet->user_id !== $user->id) {
            abort(403);
        }
    }
}
