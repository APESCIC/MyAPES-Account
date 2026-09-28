<?php

use App\Core\Extensions\Modules\ModuleManifest;
use App\Core\Extensions\Modules\ModuleStaffArea;

/**
 * APES Shelter and Rescue module package manifest (#286).
 *
 * Live prefix `/shelter` and route names `shelter.*` stay stable.
 */
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
    hubView: 'sub-cores.show',
    packagePath: __DIR__,
);
