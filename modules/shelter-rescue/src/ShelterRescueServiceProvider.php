<?php

namespace Modules\ShelterRescue;

use App\Core\Extensions\Modules\ModuleManifest;
use App\Core\Extensions\Modules\ModuleServiceProvider;
use App\Core\Extensions\Modules\ModuleStaffArea;

class ShelterRescueServiceProvider extends ModuleServiceProvider
{
    protected function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            slug: 'shelter-rescue',
            name: 'APES Shelter and Rescue',
            description: 'Animal rescue, shelter and rehabilitation services.',
            routePrefix: '/shelter',
            routeNamePrefix: 'shelter.',
            hubRouteName: 'shelter.index',
            icon: 'house',
            sortOrder: 20,
            staffArea: new ModuleStaffArea('shelter.index'),
            plugins: ['pet-profiles', 'cases', 'tickets'],
        );
    }
}
