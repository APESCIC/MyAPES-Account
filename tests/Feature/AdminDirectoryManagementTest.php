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

    public function test_mapping_changes_resynchronize_matching_users_and_only_invalidate_changed_authorization(): void
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
        $matching = User::factory()
            ->accessLevel(User::ROLE_STAFF)
            ->cloudronIdentity('matching-subject')
            ->create()
            ->refresh();
        $unrelated = User::factory()
            ->accessLevel(User::ROLE_STAFF)
            ->cloudronIdentity('unrelated-subject')
            ->create()
            ->refresh();
        $synchronizer = app(DirectoryRoleSynchronizer::class);
        $synchronizer->synchronize($matching, [
            'myapes.staff',
            'myapes.case-reviewers',
        ]);
        $synchronizer->synchronize($unrelated, ['myapes.staff']);
        $matchingEpoch = $matching->fresh()->authorization_epoch;
        $unrelatedEpoch = $unrelated->fresh()->authorization_epoch;

        $this->actingAs($superAdmin)
            ->post("/admin/groups/{$group->id}/mappings", [
                'role_id' => $role->id,
            ])
            ->assertRedirect('/admin/groups');

        $mapping = DirectoryGroupRoleMapping::query()
            ->where('directory_group_id', $group->id)
            ->where('role_id', $role->id)
            ->sole();
        $this->assertTrue($matching->fresh()->roles->contains($role));
        $this->assertSame(
            $matchingEpoch + 1,
            $matching->fresh()->authorization_epoch,
        );
        $this->assertFalse($unrelated->fresh()->roles->contains($role));
        $this->assertSame(
            $unrelatedEpoch,
            $unrelated->fresh()->authorization_epoch,
        );

        $createdAudit = AuditLog::query()
            ->where('event', 'authorization.directory_mapping_changed')
            ->latest('id')
            ->firstOrFail();
        $this->assertSame('created', $createdAudit->context['action']);
        $this->assertSame($group->id, $createdAudit->context['group_id']);
        $this->assertSame($role->id, $createdAudit->context['role_id']);
        $this->assertSame(1, $createdAudit->context['matched_user_count']);
        $this->assertSame(1, $createdAudit->context['changed_user_count']);

        $epochAfterCreate = $matching->fresh()->authorization_epoch;
        $this->actingAs($superAdmin)
            ->delete("/admin/groups/mappings/{$mapping->id}")
            ->assertRedirect('/admin/groups');

        $this->assertFalse($matching->fresh()->roles->contains($role));
        $this->assertSame(
            $epochAfterCreate + 1,
            $matching->fresh()->authorization_epoch,
        );
        $this->assertDatabaseMissing(
            'directory_group_role_mappings',
            ['id' => $mapping->id],
        );

        $removedAudit = AuditLog::query()
            ->where('event', 'authorization.directory_mapping_changed')
            ->latest('id')
            ->firstOrFail();
        $this->assertSame('removed', $removedAudit->context['action']);
        $this->assertSame(1, $removedAudit->context['changed_user_count']);
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
