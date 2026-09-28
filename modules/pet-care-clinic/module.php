<?php

use App\Core\Extensions\Modules\ModuleManifest;
use App\Core\Extensions\Modules\ModuleStaffArea;

/**
 * APES Pet Care Clinic module package manifest (#285).
 *
 * Live prefix `/petcare` and route names `petcare.*` stay stable.
 */
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
    hubView: 'sub-cores.show',
    packagePath: __DIR__,
);
