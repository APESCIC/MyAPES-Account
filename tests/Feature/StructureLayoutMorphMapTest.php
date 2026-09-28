<?php

namespace Tests\Feature;

use App\Core\Eloquent\MorphMap;
use App\Core\Providers\CoreServiceProvider;
use App\Models\ShelterCase;
use App\Models\SupportAttachment;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\AuditLogger;
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
}
