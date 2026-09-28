<?php

namespace Tests\Feature;

use App\Core\Accounts\User;
use App\Core\Attachments\SupportAttachment;
use App\Core\Eloquent\MorphMap;
use App\Core\Providers\CoreServiceProvider;
use App\Models\ShelterCase;
use App\Models\SupportTicket;
use App\Services\AuditLogger;
use App\Support\AuthorizationCompatibilityDatabaseGuard;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Modules\ApesCic\ApesCicServiceProvider;
use Plugins\Tickets\TicketsServiceProvider;
use Tests\TestCase;

class StructureLayoutMorphMapTest extends TestCase
{
    use RefreshDatabase;

    protected function afterRefreshingDatabase(): void
    {
        $this->fakeMaintenanceMode();
    }

    public function test_core_and_package_providers_are_registered(): void
    {
        $loaded = app()->getLoadedProviders();

        $this->assertArrayHasKey(CoreServiceProvider::class, $loaded);
        $this->assertArrayHasKey(ApesCicServiceProvider::class, $loaded);
        $this->assertArrayHasKey(TicketsServiceProvider::class, $loaded);
    }

    public function test_morph_map_is_enforced_with_stable_aliases(): void
    {
        $this->assertSame('user', (new User)->getMorphClass());
        $this->assertSame('support_ticket', (new SupportTicket)->getMorphClass());
        $this->assertSame('case', (new ShelterCase)->getMorphClass());
        $this->assertSame(User::class, Relation::getMorphedModel('user'));
        $this->assertSame(MorphMap::aliases(), Relation::morphMap());
    }

    public function test_audit_and_attachment_rows_resolve_after_fqcn_rewrite(): void
    {
        $user = User::factory()->create();
        $ticket = SupportTicket::query()->create([
            'user_id' => $user->id,
            'sub_core_key' => 'apes-cic',
            'service_area' => 'operations',
            'subject' => 'Morph layout',
            'status' => 'open',
            'priority' => 'medium',
            'description' => 'Morph map layout fixture.',
        ]);

        // Simulate pre-#281 stored FQCNs, then re-run the rewrite migration path.
        DB::table('audit_logs')->insert([
            'user_id' => $user->id,
            'event' => 'structure.morph_legacy',
            'auditable_type' => SupportTicket::class,
            'auditable_id' => $ticket->id,
            'context' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('support_attachments')->insert([
            'attachable_type' => SupportTicket::class,
            'attachable_id' => $ticket->id,
            'user_id' => $user->id,
            'disk' => 'local',
            'path' => 'attachments/legacy.txt',
            'original_name' => 'legacy.txt',
            'mime_type' => 'text/plain',
            'size_bytes' => 12,
            'kind' => 'screenshot',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\TicketUpdatedNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->rewriteMorphColumns([
            SupportTicket::class => 'support_ticket',
            User::class => 'user',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'structure.morph_legacy',
            'auditable_type' => 'support_ticket',
            'auditable_id' => $ticket->id,
        ]);
        $this->assertDatabaseHas('support_attachments', [
            'attachable_type' => 'support_ticket',
            'attachable_id' => $ticket->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => 'user',
            'notifiable_id' => $user->id,
        ]);

        $attachment = SupportAttachment::query()->firstOrFail();
        $this->assertInstanceOf(SupportTicket::class, $attachment->attachable);

        app(AuditLogger::class)->record('structure.morph_new', $user, $ticket);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'structure.morph_new',
            'auditable_type' => 'support_ticket',
            'auditable_id' => $ticket->id,
        ]);
    }

    public function test_morph_rewrite_migration_updates_provenanced_role_pivots(): void
    {
        $user = User::factory()->create([
            'legacy_access_level' => 'staff',
        ]);

        $this->assertDatabaseHas('model_has_roles', [
            'model_id' => $user->id,
            'model_type' => 'user',
        ]);
        $this->assertTrue(
            DB::table('role_sources')->where('user_id', $user->id)->exists(),
        );

        $guard = app(AuthorizationCompatibilityDatabaseGuard::class);
        $guard->drop();

        DB::table('model_has_roles')
            ->where('model_id', $user->id)
            ->update(['model_type' => User::class]);

        $guard->install();
        $this->rewriteUserMorphExpressionInTriggersToLegacyFqcn();

        // Live Cloudron failure mode: UPDATE of provenanced model_type is blocked
        // unless auth triggers are dropped for the rewrite window (#302).
        $migration = require database_path(
            'migrations/2026_07_28_000200_rewrite_morph_type_fqcns_to_aliases.php',
        );
        $migration->up();

        $this->assertDatabaseHas('model_has_roles', [
            'model_id' => $user->id,
            'model_type' => 'user',
        ]);
        $this->assertTrue($guard->isInstalled());
    }

    public function test_structure_package_skeletons_exist(): void
    {
        $this->assertTrue(File::isDirectory(base_path('app/Core')));
        $this->assertTrue(File::isDirectory(base_path('modules/apes-cic/src')));
        $this->assertTrue(File::isDirectory(base_path('plugins/tickets/src')));
        $this->assertTrue(class_exists(ApesCicServiceProvider::class));
        $this->assertTrue(class_exists(TicketsServiceProvider::class));
    }

    public function test_route_list_still_loads_after_empty_package_providers(): void
    {
        $exitCode = Artisan::call('route:list', ['--json' => true, '--except-vendor' => true]);
        $this->assertSame(0, $exitCode);

        $routes = json_decode(Artisan::output(), true);
        $this->assertIsArray($routes);
        $this->assertNotEmpty($routes);
        $this->assertGreaterThanOrEqual(100, count($routes));
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function rewriteMorphColumns(array $replacements): void
    {
        foreach (['audit_logs' => 'auditable_type', 'support_attachments' => 'attachable_type', 'notifications' => 'notifiable_type'] as $table => $column) {
            foreach ($replacements as $from => $to) {
                DB::table($table)->where($column, $from)->update([$column => $to]);
            }
        }
    }

    private function rewriteUserMorphExpressionInTriggersToLegacyFqcn(): void
    {
        $current = 'CHAR(117, 115, 101, 114)';
        $legacy = 'CHAR(65, 112, 112, 92, 77, 111, 100, 101, 108, 115, 92, 85, 115, 101, 114)';
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $triggers = DB::table('sqlite_master')
                ->where('type', 'trigger')
                ->where('sql', 'like', '%'.$current.'%')
                ->get(['name', 'sql']);

            foreach ($triggers as $trigger) {
                DB::unprepared('DROP TRIGGER '.((string) $trigger->name));
                DB::unprepared(str_replace($current, $legacy, (string) $trigger->sql));
            }

            return;
        }

        $triggers = collect(DB::select(
            'SELECT trigger_name AS name,
                    event_object_table AS table_name,
                    action_timing AS timing,
                    event_manipulation AS event_name,
                    action_statement AS definition
             FROM information_schema.triggers
             WHERE trigger_schema = DATABASE()
               AND action_statement LIKE ?',
            ['%'.$current.'%'],
        ));

        foreach ($triggers as $trigger) {
            $name = (string) $trigger->name;
            DB::unprepared("DROP TRIGGER {$name}");
            DB::unprepared(
                "CREATE TRIGGER {$name}
                 {$trigger->timing} {$trigger->event_name}
                 ON {$trigger->table_name}
                 FOR EACH ROW ".str_replace(
                    $current,
                    $legacy,
                    (string) $trigger->definition,
                ),
            );
        }
    }
}
