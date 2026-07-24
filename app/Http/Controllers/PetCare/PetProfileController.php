<?php

namespace App\Http\Controllers\PetCare;

use App\Http\Controllers\Controller;
use App\Models\PetProfile;
use App\Services\AuditLogger;
use App\Services\SecureUploadService;
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
        $this->authorizePet($pet, PetProfile::DOMAIN_PETCARE);

        return view('petcare.pets.show', ['pet' => $pet]);
    }

    public function update(
        Request $request,
        PetProfile $pet,
        SecureUploadService $secureUploadService,
        AuditLogger $auditLogger
    ): RedirectResponse {
        $this->authorizePet($pet, PetProfile::DOMAIN_PETCARE);

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
