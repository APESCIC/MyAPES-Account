<?php

namespace Tests\Feature;

use App\Jobs\RunDirectorySync;
use App\Models\AuditLog;
use App\Models\DirectoryGroup;
use App\Models\DirectoryGroupRoleMapping;
use App\Models\Role;
use App\Models\User;
use App\Services\DirectoryRoleSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminDirectoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_mapping_routes_are_not_available(): void
    {
        $superAdmin = $this->userWithAccess(User::ROLE_SUPERADMIN);
        $group = DirectoryGroup::query()->create([
            'name' => 'myapes.case-reviewers',
            'status' => DirectoryGroup::STATUS_PRESENT,
            'member_count' => 1,
        ]);
        $role = Role::query()->create([
            'name' => 'case-reviewer',
            'guard_name' => 'web',
        ]);

        $this->actingAs($superAdmin)
            ->post("/admin/groups/{$group->id}/mappings", [
                'role_id' => $role->id,
            ])
            ->assertNotFound();

        $mapping = (new DirectoryGroupRoleMapping)->forceFill([
            'directory_group_id' => $group->id,
            'role_id' => $role->id,
            'is_immutable' => false,
        ]);
        $mapping->save();

        $this->actingAs($superAdmin)
            ->delete("/admin/groups/mappings/{$mapping->id}")
            ->assertNotFound();
    }

    public function test_manual_sync_dispatches_existing_job_and_audits_only_safe_context(): void
    {
        Queue::fake();
        config(['queue.default' => 'database']);
        $superAdmin = $this->userWithAccess(User::ROLE_SUPERADMIN);

        $this->actingAs($superAdmin)
            ->post('/admin/groups/sync')
            ->assertRedirect('/admin/groups');

        Queue::assertPushed(RunDirectorySync::class, 1);
        $audit = AuditLog::query()
            ->where('event', 'authorization.directory_sync_requested')
            ->sole();
        $this->assertSame($superAdmin->id, $audit->user_id);
        $this->assertSame($superAdmin->id, $audit->context['actor_id']);
        $this->assertSame('manual', $audit->context['source_key']);
        $this->assertSame(
            ['actor_id', 'source_key'],
            array_keys($audit->context),
        );
    }

    public function test_production_redis_dispatch_matches_the_default_worker_connection(): void
    {
        Queue::fake();
        config([
            'queue.default' => 'redis',
            'queue.connections.redis.driver' => 'redis',
        ]);
        $superAdmin = $this->userWithAccess(User::ROLE_SUPERADMIN);

        $this->actingAs($superAdmin)
            ->post('/admin/groups/sync')
            ->assertRedirect('/admin/groups');

        Queue::assertPushed(
            RunDirectorySync::class,
            static fn (RunDirectorySync $job): bool => $job->connection
                === 'redis',
        );
    }

    public function test_manual_sync_uses_a_failover_graph_when_every_child_is_asynchronous(): void
    {
        Queue::fake();
        config([
            'queue.default' => 'failover',
            'queue.connections.failover' => [
                'driver' => 'failover',
                'connections' => ['redis', 'database'],
            ],
        ]);
        $superAdmin = $this->userWithAccess(User::ROLE_SUPERADMIN);

        $this->actingAs($superAdmin)
            ->post('/admin/groups/sync')
            ->assertRedirect('/admin/groups');

        Queue::assertPushed(
            RunDirectorySync::class,
            static fn (RunDirectorySync $job): bool => $job->connection
                === 'failover',
        );
    }

    public function test_manual_sync_rejects_a_nested_synchronous_failover_without_dispatch(): void
    {
        Queue::fake();
        config([
            'queue.default' => 'outer-failover',
            'queue.connections.outer-failover' => [
                'driver' => 'failover',
                'connections' => ['database', 'inner-failover'],
            ],
            'queue.connections.inner-failover' => [
                'driver' => 'failover',
                'connections' => ['redis', 'deferred'],
            ],
        ]);
        $superAdmin = $this->userWithAccess(User::ROLE_SUPERADMIN);

        $this->actingAs($superAdmin)
            ->from('/admin/groups')
            ->post('/admin/groups/sync')
            ->assertRedirect('/admin/groups')
            ->assertSessionHasErrors('authorization');

        Queue::assertNothingPushed();
        $this->assertDatabaseMissing('audit_logs', [
            'event' => 'authorization.directory_sync_requested',
        ]);
    }

    /**
     * @param  array<string, mixed>  $queueConfiguration
     */
    #[DataProvider('unsafeQueueGraphs')]
    public function test_manual_sync_fails_closed_for_unsafe_queue_graphs(
        array $queueConfiguration,
    ): void {
        Queue::fake();
        config($queueConfiguration);
        $superAdmin = $this->userWithAccess(User::ROLE_SUPERADMIN);

        $this->actingAs($superAdmin)
            ->from('/admin/groups')
            ->post('/admin/groups/sync')
            ->assertRedirect('/admin/groups')
            ->assertSessionHasErrors('authorization');

        Queue::assertNothingPushed();
        $this->assertDatabaseMissing('audit_logs', [
            'event' => 'authorization.directory_sync_requested',
        ]);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function unsafeQueueGraphs(): array
    {
        return [
            'direct sync' => [[
                'queue.default' => 'sync',
            ]],
            'direct deferred' => [[
                'queue.default' => 'deferred',
            ]],
            'direct background' => [[
                'queue.default' => 'background',
            ]],
            'direct null' => [[
                'queue.default' => 'null',
                'queue.connections.null' => ['driver' => 'null'],
            ]],
            'missing connection' => [[
                'queue.default' => 'not-configured',
            ]],
            'missing failover child' => [[
                'queue.default' => 'broken-failover',
                'queue.connections.broken-failover' => [
                    'driver' => 'failover',
                    'connections' => ['database', 'not-configured'],
                ],
            ]],
            'cyclic failover' => [[
                'queue.default' => 'cycle-a',
                'queue.connections.cycle-a' => [
                    'driver' => 'failover',
                    'connections' => ['cycle-b'],
                ],
                'queue.connections.cycle-b' => [
                    'driver' => 'failover',
                    'connections' => ['cycle-a'],
                ],
            ]],
        ];
    }

    private function userWithAccess(string $accessLevel): User
    {
        return User::factory()
            ->accessLevel($accessLevel)
            ->create()
            ->refresh();
    }
}
