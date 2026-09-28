<?php

namespace Tests\Feature;

use App\Contracts\ModuleRegistry;
use App\Core\Accounts\User;
use App\Core\Extensions\Modules\ModulePackageRegistry;
use App\Core\Extensions\Plugins\PluginRegistry;
use App\Services\AuthorizationProfile;
use App\Services\ModuleInstallationSynchronizer;
use App\Services\ModuleSettingsRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class StructureModulesWave4Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ModuleInstallationSynchronizer::class)->synchronize();
    }

    public function test_module_packages_expose_nav_hub_prefix_enabled_and_plugins(): void
    {
        $modules = app(ModulePackageRegistry::class);

        $apesCic = $modules->module('apes-cic');
        $this->assertSame('/apes-cic', $apesCic->routePrefix);
        $this->assertSame('apes-cic.', $apesCic->routeNamePrefix);
        $this->assertSame('apes-cic.index', $apesCic->hubRouteName);
        $this->assertSame(['tickets', 'cases', 'recruitment'], $apesCic->plugins);
        $this->assertTrue($modules->isEnabled('apes-cic'));
        $this->assertFileExists(base_path('modules/apes-cic/module.php'));

        $petCare = $modules->module('pet-care-clinic');
        $this->assertSame('/petcare', $petCare->routePrefix);
        $this->assertSame('petcare.', $petCare->routeNamePrefix);
        $this->assertSame('petcare.index', $petCare->hubRouteName);
        $this->assertSame(['pet-profiles', 'consultations', 'tickets'], $petCare->plugins);
        $this->assertTrue($modules->isEnabled('pet-care-clinic'));
        $this->assertFileExists(base_path('modules/pet-care-clinic/module.php'));

        $shelter = $modules->module('shelter-rescue');
        $this->assertSame('/shelter', $shelter->routePrefix);
        $this->assertSame('shelter.', $shelter->routeNamePrefix);
        $this->assertSame('shelter.index', $shelter->hubRouteName);
        $this->assertSame(['pet-profiles', 'cases', 'tickets'], $shelter->plugins);
        $this->assertTrue($modules->isEnabled('shelter-rescue'));
        $this->assertFileExists(base_path('modules/shelter-rescue/module.php'));
    }

    public function test_live_hub_prefixes_and_route_names_are_unchanged(): void
    {
        $staff = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_STAFF)
            ->create();

        $this->actingAs($staff)->get('/apes-cic')->assertOk();
        $this->actingAs($staff)->get('/petcare')->assertOk();
        $this->actingAs($staff)->get('/shelter')->assertOk();

        $this->assertTrue(Route::has('apes-cic.index'));
        $this->assertTrue(Route::has('petcare.index'));
        $this->assertTrue(Route::has('shelter.index'));
        $this->assertSame(url('/apes-cic'), route('apes-cic.index'));
        $this->assertSame(url('/petcare'), route('petcare.index'));
        $this->assertSame(url('/shelter'), route('shelter.index'));
    }

    public function test_apes_cic_settings_defaults_come_from_module_package(): void
    {
        $modules = app(ModulePackageRegistry::class);
        $settings = app(ModuleSettingsRegistry::class);

        $ticketDefaults = $modules->module('apes-cic')->defaultsFor('tickets');
        $this->assertIsArray($ticketDefaults);
        $this->assertArrayHasKey('websites', $ticketDefaults);
        $this->assertArrayHasKey('service_areas', $ticketDefaults);

        $this->assertSame(
            $ticketDefaults,
            $settings->defaults('apes-cic', 'tickets'),
        );
        $this->assertSame(
            $modules->module('apes-cic')->defaultsFor('cases'),
            $settings->defaults('apes-cic', 'cases'),
        );
        $this->assertSame(
            ['public_board_enabled' => true, 'public_apply_enabled' => true],
            $settings->defaults('apes-cic', 'recruitment'),
        );

        $registrySource = file_get_contents(app_path('Services/ModuleSettingsRegistry.php'));
        $this->assertStringNotContainsString("=== 'apes-cic'", $registrySource);
        $this->assertStringNotContainsString('ticketsForApesCic', $registrySource);
    }

    public function test_plugin_manifests_declare_per_module_dependencies(): void
    {
        $plugins = app(PluginRegistry::class);
        $registry = app(ModuleRegistry::class);

        $casesDeps = $plugins->plugin('cases')->dependencies;
        $this->assertCount(1, $casesDeps);
        $this->assertSame('pet-profiles', $casesDeps[0]->pluginSlug);
        $this->assertSame(['shelter-rescue'], $casesDeps[0]->onlyModules);
        $this->assertTrue($casesDeps[0]->appliesTo('shelter-rescue'));
        $this->assertFalse($casesDeps[0]->appliesTo('apes-cic'));

        $this->assertSame(
            ['shelter-rescue:pet-profiles'],
            $registry->instance('shelter-rescue', 'cases')->dependencyKeys(),
        );
        $this->assertSame(
            [],
            $registry->instance('apes-cic', 'cases')->dependencyKeys(),
        );

        $consultationsDeps = $plugins->plugin('consultations')->dependencies;
        $this->assertCount(1, $consultationsDeps);
        $this->assertSame('pet-profiles', $consultationsDeps[0]->pluginSlug);
        $this->assertNull($consultationsDeps[0]->onlyModules);
        $this->assertSame(
            ['pet-care-clinic:pet-profiles'],
            $registry->instance('pet-care-clinic', 'consultations')->dependencyKeys(),
        );

        $matrixSource = file_get_contents(app_path('Modules/FirstPartyModuleRegistry.php'));
        $this->assertStringNotContainsString("'shelter-rescue:cases'", $matrixSource);
        $this->assertStringNotContainsString("'pet-care-clinic:consultations'", $matrixSource);
    }

    public function test_plugin_controllers_live_in_plugin_packages_not_modules(): void
    {
        // Wave 6 (#289): Tickets + Cases moved into plugins/.
        $this->assertFileExists(base_path('plugins/tickets/src/Http/Controllers/TicketController.php'));
        $this->assertFileExists(base_path('plugins/cases/src/Http/Controllers/CaseController.php'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/ApesCic/TicketController.php'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/Shelter/CaseController.php'));

        // Wave 7 (#290): Recruitment + Consultations moved into plugins/.
        $this->assertFileExists(base_path('plugins/consultations/src/Http/Controllers/ConsultationController.php'));
        $this->assertFileExists(base_path('plugins/recruitment/src/Http/Controllers/RecruitmentRoleController.php'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/PetCare/ConsultationController.php'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/ApesCic/RecruitmentRoleController.php'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/RecruitmentBoardController.php'));

        // Controllers must not land inside organisation module packages.
        $this->assertFileDoesNotExist(base_path('modules/apes-cic/src/Http/Controllers/TicketController.php'));
        $this->assertFileDoesNotExist(base_path('modules/shelter-rescue/src/Http/Controllers/CaseController.php'));
    }
}
