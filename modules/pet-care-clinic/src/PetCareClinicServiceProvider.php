<?php

namespace Modules\PetCareClinic;

use App\Core\Extensions\Modules\ModuleManifest;
use App\Core\Extensions\Modules\ModuleServiceProvider;

class PetCareClinicServiceProvider extends ModuleServiceProvider
{
    protected function manifest(): ModuleManifest
    {
        /** @var ModuleManifest $manifest */
        $manifest = require dirname(__DIR__).'/module.php';

        return $manifest;
    }
}
