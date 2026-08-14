<?php

namespace Tests\Feature;

use App\Contracts\ModuleRegistry;
use App\Modules\ModuleCodeStatus;
use App\Modules\ModuleDependency;
use App\Modules\ModuleInstanceDefinition;
use App\Modules\Summaries\SupportTicketSummaryProvider;
use App\Services\AuthorizationProfile;
use App\Services\ModuleRegistryValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class ModuleRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_first_party_registry_exposes_the_permanent_sub_cores_and_module_types(): void
    {
        $registry = app(ModuleRegistry::class);

        $this->assertSame(
            ['apes-cic', 'pet-care-clinic', 'shelter-rescue'],
            array_keys($registry->subCores()),
        );
        $this->assertSame(
            ['cases', 'consultations', 'pet-profiles', 'tickets'],
            array_keys($registry->modules()),
        );

        $this->assertSame('/apes-cic', $registry->subCore('apes-cic')->basePath);
        $this->assertSame('/shelter', $registry->subCore('shelter-rescue')->basePath);
        $this->assertSame('/petcare', $registry->subCore('pet-care-clinic')->basePath);
    }

    public function test_all_twelve_matrix_cells_have_an_explicit_code_status(): void
    {
        $registry = app(ModuleRegistry::class);
        $matrix = collect($registry->matrix())
            ->mapWithKeys(fn ($cell): array => [$cell->key() => $cell->codeStatus])
            ->all();

        $this->assertCount(12, $matrix);
        $this->assertSame([
            'apes-cic:cases' => ModuleCodeStatus::Shipped,
            'apes-cic:consultations' => ModuleCodeStatus::Incompatible,
            'apes-cic:pet-profiles' => ModuleCodeStatus::Incompatible,
            'apes-cic:tickets' => ModuleCodeStatus::Shipped,
            'pet-care-clinic:cases' => ModuleCodeStatus::Incompatible,
            'pet-care-clinic:consultations' => ModuleCodeStatus::Shipped,
            'pet-care-clinic:pet-profiles' => ModuleCodeStatus::Shipped,
            'pet-care-clinic:tickets' => ModuleCodeStatus::CodeNotShipped,
            'shelter-rescue:cases' => ModuleCodeStatus::Shipped,
            'shelter-rescue:consultations' => ModuleCodeStatus::Incompatible,
            'shelter-rescue:pet-profiles' => ModuleCodeStatus::Shipped,
            'shelter-rescue:tickets' => ModuleCodeStatus::Shipped,
        ], $matrix);
    }

    public function test_shipped_instances_and_dependencies_are_code_owned(): void
    {
        $registry = app(ModuleRegistry::class);

        $this->assertSame([
            'apes-cic:cases',
            'apes-cic:tickets',
            'pet-care-clinic:consultations',
            'pet-care-clinic:pet-profiles',
            'shelter-rescue:cases',
            'shelter-rescue:pet-profiles',
            'shelter-rescue:tickets',
        ], array_keys($registry->shippedInstances()));

        $this->assertSame(
            ['shelter-rescue:pet-profiles'],
            $registry->instance('shelter-rescue', 'cases')->dependencyKeys(),
        );
        $this->assertSame(
            ['pet-care-clinic:pet-profiles'],
            $registry->instance('pet-care-clinic', 'consultations')->dependencyKeys(),
        );
    }

    public function test_shelter_navigation_orders_ticket_before_cases_without_changing_apes_cic(): void
    {
        $registry = app(ModuleRegistry::class);

        $this->assertSame(
            10,
            $registry->instance('apes-cic', 'tickets')
                ->module->navigation['apes-cic']->order,
        );
        $this->assertSame(
            20,
            $registry->instance('apes-cic', 'cases')
                ->module->navigation['apes-cic']->order,
        );
        $this->assertSame(
            20,
            $registry->instance('shelter-rescue', 'tickets')
                ->module->navigation['shelter-rescue']->order,
        );
        $this->assertSame(
            30,
            $registry->instance('shelter-rescue', 'cases')
                ->module->navigation['shelter-rescue']->order,
        );
    }

    public function test_registry_validation_rejects_dependency_cycles(): void
    {
        $registry = app(ModuleRegistry::class);
        $matrix = collect($registry->matrix())
            ->mapWithKeys(
                static fn (ModuleInstanceDefinition $instance): array => [
                    $instance->key() => $instance,
                ],
            )
            ->all();
        $tickets = $matrix['apes-cic:tickets'];
        $consultations = $matrix['pet-care-clinic:consultations'];
        $matrix['apes-cic:tickets'] = new ModuleInstanceDefinition(
            $tickets->subCore,
            $tickets->module,
            $tickets->codeStatus,
            [new ModuleDependency('pet-care-clinic', 'consultations')],
        );
        $matrix['pet-care-clinic:consultations'] = new ModuleInstanceDefinition(
            $consultations->subCore,
            $consultations->module,
            $consultations->codeStatus,
            [new ModuleDependency('apes-cic', 'tickets')],
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Module dependency cycle detected.');

        app(ModuleRegistryValidator::class)->validate(
            $registry->subCores(),
            $registry->modules(),
            $matrix,
        );
    }

    public function test_registry_validation_rejects_invalid_or_unavailable_instance_provider_overrides(): void
    {
        $registry = app(ModuleRegistry::class);
        $matrix = collect($registry->matrix())
            ->mapWithKeys(
                static fn (ModuleInstanceDefinition $instance): array => [
                    $instance->key() => $instance,
                ],
            )
            ->all();
        $tickets = $matrix['shelter-rescue:tickets'];
        $matrix['shelter-rescue:tickets'] = new ModuleInstanceDefinition(
            $tickets->subCore,
            $tickets->module,
            $tickets->codeStatus,
            [],
            \stdClass::class,
        );

        try {
            app(ModuleRegistryValidator::class)->validate(
                $registry->subCores(),
                $registry->modules(),
                $matrix,
            );
            $this->fail('An invalid summary-provider override was accepted.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'Module summary provider is invalid.',
                $exception->getMessage(),
            );
        }

        $unavailable = $registry->instance('pet-care-clinic', 'tickets');
        $matrix['pet-care-clinic:tickets'] = new ModuleInstanceDefinition(
            $unavailable->subCore,
            $unavailable->module,
            $unavailable->codeStatus,
            [],
            SupportTicketSummaryProvider::class,
        );

        try {
            app(ModuleRegistryValidator::class)->validate(
                $registry->subCores(),
                $registry->modules(),
                $matrix,
            );
            $this->fail('An unavailable instance override was accepted.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'Unavailable module instances cannot declare overrides.',
                $exception->getMessage(),
            );
        }
    }

    public function test_permission_descriptors_are_unique_namespaced_and_classified(): void
    {
        $registry = app(ModuleRegistry::class);
        $permissions = $registry->permissions();
        $names = array_map(
            static fn ($permission): string => $permission->name,
            $permissions,
        );

        $this->assertCount(51, $permissions);
        $this->assertCount(51, array_unique($names));
        $this->assertContains('apes-cic.cases.comment-own', $names);
        $this->assertContains('apes-cic.cases.delete', $names);
        $this->assertContains('apes-cic.tickets.view-own', $names);
        $this->assertContains('apes-cic.tickets.comment-own', $names);
        $this->assertContains('apes-cic.tickets.delete', $names);
        $this->assertContains('shelter-rescue.pet-profiles.update-own', $names);
        $this->assertContains('shelter-rescue.tickets.view-own', $names);
        $this->assertContains('shelter-rescue.tickets.delete', $names);
        $this->assertContains('pet-care-clinic.consultations.assign', $names);

        foreach ($permissions as $permission) {
            $this->assertMatchesRegularExpression(
                '/^[a-z0-9-]+\.[a-z0-9-]+\.[a-z0-9-]+$/',
                $permission->name,
            );
            $this->assertSame(
                in_array(
                    $permission->ability,
                    ['view-all', 'update-all', 'assign', 'close', 'delete'],
                    true,
                ),
                $permission->requiresDirectoryContext,
            );
        }
    }

    public function test_module_permissions_extend_protected_role_matrices_without_granting_module_management_to_administrators(): void
    {
        $profile = app(AuthorizationProfile::class);
        $matrix = $profile->permissionMatrix();

        $this->assertContains(
            'apes-cic.tickets.create',
            $matrix[AuthorizationProfile::ROLE_SERVICE_USER],
        );
        $this->assertContains(
            'apes-cic.tickets.assign',
            $matrix[AuthorizationProfile::ROLE_STAFF],
        );
        $this->assertContains(
            'pet-care-clinic.consultations.close',
            $matrix[AuthorizationProfile::ROLE_ADMINISTRATOR],
        );
        $this->assertNotContains(
            'admin.modules.manage',
            $matrix[AuthorizationProfile::ROLE_ADMINISTRATOR],
        );
        $this->assertContains(
            'admin.modules.manage',
            $matrix[AuthorizationProfile::ROLE_SUPER_ADMIN],
        );
        $this->assertTrue(
            $profile->isSuperAdminOnlyPermission('admin.modules.manage'),
        );
        $this->assertCount(64, $profile->permissions());
        $this->assertFalse(
            $profile->isDirectoryRestrictedPermission(
                'apes-cic.tickets.create',
            ),
        );
        $this->assertTrue(
            $profile->isDirectoryRestrictedPermission(
                'apes-cic.tickets.assign',
            ),
        );
    }

    public function test_authorization_profile_is_request_scoped_and_memoizes_its_permission_catalogue(): void
    {
        $registry = Mockery::mock(ModuleRegistry::class);
        $registry->shouldReceive('permissions')->once()->andReturn([]);
        Schema::shouldReceive('hasTable')
            ->once()
            ->with('module_installations')
            ->andReturnTrue();
        $profile = new AuthorizationProfile($registry);

        $firstMatrix = $profile->permissionMatrix();
        $this->assertSame($firstMatrix, $profile->permissionMatrix());
        $firstPermissions = $profile->permissions();
        $this->assertSame($firstPermissions, $profile->permissions());
        $this->assertSame(
            app(AuthorizationProfile::class),
            app(AuthorizationProfile::class),
        );
    }
}
