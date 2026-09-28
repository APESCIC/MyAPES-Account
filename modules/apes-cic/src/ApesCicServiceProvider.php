<?php

namespace Modules\ApesCic;

use App\Core\Extensions\Modules\ModuleManifest;
use App\Core\Extensions\Modules\ModuleServiceProvider;
use App\Core\Extensions\Modules\ModuleStaffArea;

class ApesCicServiceProvider extends ModuleServiceProvider
{
    protected function manifest(): ModuleManifest
    {
        return new ModuleManifest(
            slug: 'apes-cic',
            name: 'APES CIC',
            description: 'Member support and organisation services.',
            routePrefix: '/apes-cic',
            routeNamePrefix: 'apes-cic.',
            hubRouteName: 'apes-cic.index',
            icon: 'building-2',
            sortOrder: 10,
            staffArea: new ModuleStaffArea('apes-cic.index'),
            plugins: ['tickets', 'cases', 'recruitment'],
        );
    }
}
