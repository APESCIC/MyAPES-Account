<?php

namespace App\Http\Controllers\Shelter;

use App\Http\Controllers\Controller;
use App\Models\PetProfile;
use App\Services\AuditLogger;
use App\Services\SecureUploadService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PetProfileController extends Controller
{
    public function index(): View
    {
        $user = request()->user();
        Gate::authorize('viewAny', PetProfile::class);
        $query = PetProfile::query()
            ->where('service_domain', PetProfile::DOMAIN_SHELTER)
            ->visibleTo($user)
            ->latest();

        return view('shelter.pets.index', [
            'pets' => $query->paginate(20),
        ]);
    }

    public function store(
        Request $request,
        SecureUploadService $secureUploadService,
        AuditLogger $auditLogger
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'species' => ['nullable', 'string', 'max:255'],
            'age_years' => ['nullable', 'integer', 'between:0,80'],
            'sex' => ['required', 'in:male,female,unknown'],
            'neutering_status' => ['required', 'in:neutered,not_neutered,unknown'],
            'health_issues' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $pet = new PetProfile([
            ...$validated,
            'service_domain' => PetProfile::DOMAIN_SHELTER,
            'user_id' => $request->user()->id,
        ]);

        if ($request->hasFile('photo')) {
            $pet->photo_path = $secureUploadService->storeImage(
                $request->file('photo'),
                'pet-profiles',
                'photo'
            );
        }

        $pet->save();
        $auditLogger->record('shelter.pet_profile.created', $request->user(), $pet, [
            'has_photo' => $request->hasFile('photo'),
        ]);

        return redirect()->route('shelter.pets.show', $pet);
    }

    public function show(PetProfile $pet): View
    {
        $this->authorizeDomainPet($pet, PetProfile::DOMAIN_SHELTER, 'view');

        return view('shelter.pets.show', ['pet' => $pet]);
    }

    public function update(
        Request $request,
        PetProfile $pet,
        SecureUploadService $secureUploadService,
        AuditLogger $auditLogger
    ): RedirectResponse {
        $this->authorizeDomainPet($pet, PetProfile::DOMAIN_SHELTER, 'update');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'species' => ['nullable', 'string', 'max:255'],
            'age_years' => ['nullable', 'integer', 'between:0,80'],
            'sex' => ['required', 'in:male,female,unknown'],
            'neutering_status' => ['required', 'in:neutered,not_neutered,unknown'],
            'health_issues' => ['nullable', 'string'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        if ($request->hasFile('photo')) {
            $previousPhotoPath = $pet->photo_path;
            $validated['photo_path'] = $secureUploadService->storeImage(
                $request->file('photo'),
                'pet-profiles',
                'photo'
            );
            if (is_string($previousPhotoPath) && $previousPhotoPath !== '' && $previousPhotoPath !== $validated['photo_path']) {
                $secureUploadService->deleteIfPresent($previousPhotoPath);
            }
        }

        $pet->update($validated);
        $auditLogger->record('shelter.pet_profile.updated', $request->user(), $pet, [
            'photo_replaced' => $request->hasFile('photo'),
        ]);

        return redirect()->route('shelter.pets.show', $pet)->with('status', 'Pet profile updated.');
    }

    private function authorizeDomainPet(
        PetProfile $pet,
        string $domain,
        string $ability,
    ): void {
        if ($pet->service_domain !== $domain) {
            abort(404);
        }

        Gate::authorize($ability, $pet);
    }
}
