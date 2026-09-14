<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

final class StaffPetCreateReturn
{
    public const SHELTER_CASES = 'shelter.cases';

    public const PETCARE_CONSULTATIONS = 'petcare.consultations';

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return [
            self::SHELTER_CASES,
            self::PETCARE_CONSULTATIONS,
        ];
    }

    public static function isAllowed(mixed $key): bool
    {
        return is_string($key) && in_array($key, self::keys(), true);
    }

    public static function addPetUrl(string $petsIndexRoute, string $returnKey): string
    {
        return route($petsIndexRoute, ['return_to' => $returnKey]).'#create';
    }

    public static function continueUrl(mixed $key, int $petProfileId): ?string
    {
        if (! self::isAllowed($key)) {
            return null;
        }

        $route = match ($key) {
            self::SHELTER_CASES => 'shelter.cases.index',
            self::PETCARE_CONSULTATIONS => 'petcare.consultations.index',
            default => null,
        };

        if ($route === null) {
            return null;
        }

        return route($route, ['pet_profile_id' => $petProfileId]).'#create';
    }

    /**
     * @param  Collection<int, mixed>  $petProfiles
     * @return array{showEmptyPetSelect: bool, addPetUrl: string|null}
     */
    public static function emptySelectViewData(
        User $user,
        Collection $petProfiles,
        string $createPetPermission,
        string $petsIndexRoute,
        string $returnKey,
    ): array {
        $showEmptyPetSelect = $user->isStaff() && $petProfiles->isEmpty();
        $canAddPet = $showEmptyPetSelect
            && $user->can($createPetPermission);

        return [
            'showEmptyPetSelect' => $showEmptyPetSelect,
            'addPetUrl' => $canAddPet
                ? self::addPetUrl($petsIndexRoute, $returnKey)
                : null,
        ];
    }

    public static function requestedKey(mixed $value): ?string
    {
        return self::isAllowed($value) ? $value : null;
    }
}
