<?php

namespace App\Http\Controllers\PetCare;

use App\Http\Controllers\Controller;
use App\Models\PetProfile;
use App\Services\AuditLogger;
use App\Services\PetProfilePhotoResponder;
use App\Services\SecureUploadService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PetProfileController extends Controller
{
    public function index(): View
    {
        $user = request()->user();
        Gate::authorize('viewAny', PetProfile::class);
        $query = PetProfile::query()
            ->where('service_domain', PetProfile::DOMAIN_PETCARE)
            ->visibleTo($user)
            ->latest();

        return view('petcare.pets.index', [
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
            'service_domain' => PetProfile::DOMAIN_PETCARE,
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
        $auditLogger->record('petcare.pet_profile.created', $request->user(), $pet, [
            'has_photo' => $request->hasFile('photo'),
        ]);

        return redirect()->route('petcare.pets.show', $pet);
    }

    public function show(PetProfile $pet): View
    {
        $this->authorizeDomainPet($pet, PetProfile::DOMAIN_PETCARE, 'view');

        return view('petcare.pets.show', ['pet' => $pet]);
    }

    public function photo(
        PetProfile $pet,
        PetProfilePhotoResponder $photos,
    ): StreamedResponse {
        return $photos->response($pet, PetProfile::DOMAIN_PETCARE);
    }

    public function update(
        Request $request,
        PetProfile $pet,
        SecureUploadService $secureUploadService,
        AuditLogger $auditLogger
    ): RedirectResponse {
        $this->authorizeDomainPet($pet, PetProfile::DOMAIN_PETCARE, 'update');

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
        $auditLogger->record('petcare.pet_profile.updated', $request->user(), $pet, [
            'photo_replaced' => $request->hasFile('photo'),
        ]);

        return redirect()->route('petcare.pets.show', $pet)->with('status', 'Pet profile updated.');
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
