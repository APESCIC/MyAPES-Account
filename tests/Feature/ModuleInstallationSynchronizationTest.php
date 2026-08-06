<?php

namespace Tests\Feature;

use App\Models\ModuleInstallation;
use App\Models\User;
use App\Services\ModuleInstallationSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ModuleInstallationSynchronizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_portable_installation_schema_records_lifecycle_actors_and_transitions(): void
    {
        $this->assertTrue(Schema::hasColumns('module_installations', [
            'id',
            'sub_core_key',
            'module_key',
            'enabled',
            'installed_at',
            'installed_by',
            'enabled_at',
            'enabled_by',
            'disabled_at',
            'disabled_by',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_synchronization_creates_exactly_the_five_shipped_defaults(): void
    {
        $result = app(ModuleInstallationSynchronizer::class)->synchronize();

        $this->assertSame(['created' => 0, 'existing' => 5], $result);
        $this->assertDatabaseCount('module_installations', 5);
        $this->assertSame([
            'apes-cic:tickets',
            'pet-care-clinic:consultations',
            'pet-care-clinic:pet-profiles',
            'shelter-rescue:cases',
            'shelter-rescue:pet-profiles',
        ], ModuleInstallation::query()
            ->orderBy('sub_core_key')
            ->orderBy('module_key')
            ->get()
            ->map->instanceKey()
            ->all());

        foreach (ModuleInstallation::all() as $installation) {
            $this->assertTrue($installation->enabled);
            $this->assertNotNull($installation->installed_at);
            $this->assertNotNull($installation->enabled_at);
            $this->assertNull($installation->installed_by);
            $this->assertNull($installation->enabled_by);
            $this->assertNull($installation->disabled_at);
            $this->assertNull($installation->disabled_by);
        }
    }

    public function test_synchronization_never_reenables_or_rewrites_existing_installations(): void
    {
        Carbon::setTestNow('2026-08-06 10:00:00');
        $synchronizer = app(ModuleInstallationSynchronizer::class);
        $synchronizer->synchronize();
        $actor = User::factory()->create();
        $installation = ModuleInstallation::query()
            ->where('sub_core_key', 'apes-cic')
            ->where('module_key', 'tickets')
            ->firstOrFail();

        Carbon::setTestNow('2026-08-06 11:00:00');
        $installation->forceFill([
            'enabled' => false,
            'disabled_at' => now(),
            'disabled_by' => $actor->id,
        ])->save();
        $preserved = $installation->fresh()->getRawOriginal();

        Carbon::setTestNow('2026-08-06 12:00:00');
        $result = $synchronizer->synchronize();
        $actual = $installation->fresh()->getRawOriginal();

        $this->assertSame(['created' => 0, 'existing' => 5], $result);
        $this->assertSame($preserved, $actual);
        $this->assertFalse($installation->fresh()->enabled);
    }

    public function test_synchronization_recreates_only_missing_shipped_defaults(): void
    {
        $synchronizer = app(ModuleInstallationSynchronizer::class);
        $synchronizer->synchronize();
        ModuleInstallation::query()
            ->where('sub_core_key', 'shelter-rescue')
            ->where('module_key', 'cases')
            ->delete();

        $this->assertSame(
            ['created' => 1, 'existing' => 4],
            $synchronizer->synchronize(),
        );
        $this->assertDatabaseCount('module_installations', 5);
        $this->assertDatabaseMissing('module_installations', [
            'sub_core_key' => 'shelter-rescue',
            'module_key' => 'tickets',
        ]);
    }

    public function test_module_lifecycle_commands_validate_and_synchronize_the_registry(): void
    {
        $this->artisan('myapes:modules:preflight')
            ->expectsOutputToContain('Module registry: ok (3 sub-cores, 4 module types)')
            ->assertSuccessful();

        $this->artisan('myapes:modules:sync')
            ->expectsOutputToContain('Module synchronization: ok (0 created, 5 existing)')
            ->assertSuccessful();

        $this->artisan('myapes:modules:sync')
            ->expectsOutputToContain('Module synchronization: ok (0 created, 5 existing)')
            ->assertSuccessful();

        $this->artisan('myapes:modules:check')
            ->expectsOutputToContain('Module integrity: ok (5 installations)')
            ->assertSuccessful();
    }
}
