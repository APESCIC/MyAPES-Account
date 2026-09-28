<?php

use App\Core\Providers\CoreServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\ModuleServiceProvider;
use Modules\ApesCic\ApesCicServiceProvider;
use Modules\PetCareClinic\PetCareClinicServiceProvider;
use Modules\ShelterRescue\ShelterRescueServiceProvider;
use Plugins\Cases\CasesServiceProvider;
use Plugins\Consultations\ConsultationsServiceProvider;
use Plugins\PetProfiles\PetProfilesServiceProvider;
use Plugins\Recruitment\RecruitmentServiceProvider;
use Plugins\Tickets\TicketsServiceProvider;

return [
    CoreServiceProvider::class,
    ModuleServiceProvider::class,
    AppServiceProvider::class,
    ApesCicServiceProvider::class,
    PetCareClinicServiceProvider::class,
    ShelterRescueServiceProvider::class,
    TicketsServiceProvider::class,
    CasesServiceProvider::class,
    RecruitmentServiceProvider::class,
    ConsultationsServiceProvider::class,
    PetProfilesServiceProvider::class,
];
