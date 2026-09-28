<?php

namespace Plugins\PetProfiles;

use InvalidArgumentException;

/**
 * Per-module presentation and domain mapping for the shared Pet Profiles plugin (#291).
 */
final readonly class PetProfilesArea
{
    public function __construct(
        public string $moduleSlug,
        public string $serviceDomain,
        public string $routeNamePrefix,
        public string $auditEventPrefix,
        public string $serviceLabel,
        public string $serviceLabelClass,
        public string $indexTitle,
        public string $showTitlePrefix,
        public string $createFlash,
        public string $updateFlash,
        public bool $showOwnerMeta,
        public bool $flashOnCreateRedirect,
        public bool $requireIndexAbility,
    ) {}

    public static function forModule(string $moduleSlug): self
    {
        return match ($moduleSlug) {
            'shelter-rescue' => new self(
                moduleSlug: 'shelter-rescue',
                serviceDomain: 'shelter',
                routeNamePrefix: 'shelter.pets',
                auditEventPrefix: 'shelter.pet_profile',
                serviceLabel: 'APES Shelter and Rescue',
                serviceLabelClass: 'apes-shelter',
                indexTitle: 'Shelter Pet Profiles',
                showTitlePrefix: 'Shelter Pet',
                createFlash: 'Your pet has been saved.',
                updateFlash: 'Your pet has been saved.',
                showOwnerMeta: true,
                flashOnCreateRedirect: true,
                requireIndexAbility: true,
            ),
            'pet-care-clinic' => new self(
                moduleSlug: 'pet-care-clinic',
                serviceDomain: 'petcare',
                routeNamePrefix: 'petcare.pets',
                auditEventPrefix: 'petcare.pet_profile',
                serviceLabel: 'APES Pet Care Clinic',
                serviceLabelClass: 'apes-petcare',
                indexTitle: 'APES Pet Care Clinic Pet Profiles',
                showTitlePrefix: 'APES Pet Care Clinic Pet',
                createFlash: 'Your pet has been saved.',
                updateFlash: 'Pet profile updated.',
                showOwnerMeta: false,
                flashOnCreateRedirect: false,
                requireIndexAbility: false,
            ),
            default => throw new InvalidArgumentException(
                "Pet Profiles is not configured for module [{$moduleSlug}].",
            ),
        };
    }

    public function permission(string $ability): string
    {
        return "{$this->moduleSlug}.pet-profiles.{$ability}";
    }

    public function route(string $action, mixed $parameters = []): string
    {
        return route("{$this->routeNamePrefix}.{$action}", $parameters);
    }

    public function indexRouteName(): string
    {
        return "{$this->routeNamePrefix}.index";
    }

    public function showRouteName(): string
    {
        return "{$this->routeNamePrefix}.show";
    }

    public function storeRouteName(): string
    {
        return "{$this->routeNamePrefix}.store";
    }

    public function updateRouteName(): string
    {
        return "{$this->routeNamePrefix}.update";
    }

    public function photoRouteName(): string
    {
        return "{$this->routeNamePrefix}.photo";
    }
}
