<?php

namespace Modules\PetCareClinic;

use App\Core\Extensions\Modules\ModuleManifest;
use App\Core\Extensions\Modules\ModuleServiceProvider;
use App\Core\Extensions\Modules\ModuleStaffArea;

class PetCareClinicServiceProvider extends ModuleServiceProvider
{
    protected function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            slug: 'pet-care-clinic',
            name: 'APES Pet Care Clinic',
            description: 'Pet care records and clinical consultations.',
            routePrefix: '/petcare',
            routeNamePrefix: 'petcare.',
            hubRouteName: 'petcare.index',
            icon: 'heart-pulse',
            sortOrder: 30,
            staffArea: new ModuleStaffArea('petcare.index'),
            plugins: ['pet-profiles', 'consultations', 'tickets'],
        );
    }
}
