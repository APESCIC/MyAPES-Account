<?php

namespace Tests\Feature;

use App\Contracts\ModuleAnalyticsProvider;
use App\Contracts\ModuleAggregateSummaryProvider;
use App\Contracts\ModuleRecentActivityProvider;
use App\Contracts\ModuleRegistry;
use App\Models\CaseUpdate;
use App\Models\ModuleInstallation;
use App\Models\ShelterCase;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\AuthorizationProfile;
use App\Services\ModuleInstallationSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ModuleProviderContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ModuleInstallationSynchronizer::class)->synchronize();
    }

    public function test_ticket_and_case_definitions_expose_optional_typed_provider_slots(): void
    {
        $registry = app(ModuleRegistry::class);

        foreach (['tickets', 'cases'] as $moduleKey) {
            $module = $registry->module($moduleKey);
            $this->assertTrue(is_a(
                $module->recentActivityProvider,
                ModuleRecentActivityProvider::class,
                true,
            ));
            $this->assertTrue(is_a(
                $module->analyticsProvider,
                ModuleAnalyticsProvider::class,
                true,
            ));
        }

        $this->assertNull($registry->module('pet-profiles')->recentActivityProvider);
        $this->assertNull($registry->module('consultations')->analyticsProvider);
    }

    public function test_case_analytics_are_instance_scoped_and_exact_for_the_requested_range(): void
    {
        $owner = User::factory()->create();
        $registry = app(ModuleRegistry::class);
        $instance = $registry->instance('apes-cic', 'cases');
        Carbon::setTestNow('2026-08-03 12:00:00');
        $this->caseFor($owner, [
            'priority' => 'urgent',
            'created_at' => '2026-08-01 09:00:00',
            'updated_at' => '2026-08-01 09:00:00',
            'opened_at' => '2026-08-01 09:00:00',
        ]);
        $this->caseFor($owner, [
            'status' => 'closed',
            'assigned_to' => $owner->id,
            'created_at' => '2026-08-01 10:00:00',
            'updated_at' => '2026-08-02 10:00:00',
            'opened_at' => '2026-08-01 10:00:00',
            'closed_at' => '2026-08-02 10:00:00',
        ]);
        $this->caseFor($owner, [
            'sub_core_key' => 'shelter-rescue',
            'case_type' => 'rescue',
            'category' => null,
            'priority' => 'urgent',
        ]);

        /** @var ModuleAnalyticsProvider $provider */
        $provider = app($instance->module->analyticsProvider);
        $snapshot = $provider->snapshot(
            $instance,
            Carbon::parse('2026-08-01 00:00:00', 'Europe/London'),
            Carbon::parse('2026-08-04 00:00:00', 'Europe/London'),
            'Europe/London',
        );

        $this->assertSame(2, $snapshot->total);
        $this->assertSame(1, $snapshot->open);
        $this->assertSame(1, $snapshot->highOrUrgent);
        $this->assertSame(1, $snapshot->unassigned);
        $this->assertSame(['2026-08-01' => 2], $snapshot->createdPerDay);
        $this->assertSame(['2026-08-02' => 1], $snapshot->closedPerDay);
        $this->assertSame(1440.0, $snapshot->medianClosureMinutes);
        $this->assertSame(1, $snapshot->closureSampleSize);
    }

    public function test_apes_cic_case_analytics_use_the_first_terminal_timestamp(): void
    {
        $owner = User::factory()->create();
        $registry = app(ModuleRegistry::class);
        $instance = $registry->instance('apes-cic', 'cases');
        $this->caseFor($owner, [
            'status' => 'closed',
            'opened_at' => Carbon::parse('2026-08-01 00:30:00', 'UTC'),
            'resolved_at' => Carbon::parse('2026-08-02 00:30:00', 'UTC'),
            'closed_at' => Carbon::parse('2026-08-03 00:30:00', 'UTC'),
        ]);
        $this->caseFor($owner, [
            'status' => 'closed',
            'opened_at' => Carbon::parse('2026-08-02 00:00:00', 'UTC'),
            'closed_at' => Carbon::parse('2026-08-03 00:30:00', 'UTC'),
        ]);
        $this->caseFor($owner, [
            'status' => 'resolved',
            'opened_at' => Carbon::parse('2026-08-02 09:00:00', 'UTC'),
            'resolved_at' => Carbon::parse('2026-08-02 12:00:00', 'UTC'),
        ]);

        /** @var ModuleAnalyticsProvider $provider */
        $provider = app($instance->module->analyticsProvider);
        $snapshot = $provider->snapshot(
            $instance,
            Carbon::parse('2026-08-02 00:00:00', 'Europe/London'),
            Carbon::parse('2026-08-04 00:00:00', 'Europe/London'),
            'Europe/London',
        );

        $this->assertSame([
            '2026-08-02' => 2,
            '2026-08-03' => 1,
        ], $snapshot->closedPerDay);
        $this->assertSame(1440.0, $snapshot->medianClosureMinutes);
        $this->assertSame(3, $snapshot->closureSampleSize);
    }

    public function test_apes_cic_case_analytics_exclude_terminal_timestamp_at_exclusive_to_boundary(): void
    {
        $owner = User::factory()->create();
        $registry = app(ModuleRegistry::class);
        $instance = $registry->instance('apes-cic', 'cases');
        $this->caseFor($owner, [
            'status' => 'resolved',
            'opened_at' => Carbon::parse('2026-08-03 00:00:00', 'UTC'),
            'resolved_at' => Carbon::parse('2026-08-03 23:00:00', 'UTC'),
        ]);

        /** @var ModuleAnalyticsProvider $provider */
        $provider = app($instance->module->analyticsProvider);
        $snapshot = $provider->snapshot(
            $instance,
            Carbon::parse('2026-08-02 00:00:00', 'Europe/London'),
            Carbon::parse('2026-08-04 00:00:00', 'Europe/London'),
            'Europe/London',
        );

        $this->assertSame([], $snapshot->closedPerDay);
        $this->assertNull($snapshot->medianClosureMinutes);
        $this->assertSame(0, $snapshot->closureSampleSize);
    }

    public function test_apes_cic_case_summaries_treat_resolved_and_closed_cases_as_terminal(): void
    {
        $owner = User::factory()->create();
        $registry = app(ModuleRegistry::class);
        $instance = $registry->instance('apes-cic', 'cases');
        $this->caseFor($owner, ['status' => 'open']);
        $this->caseFor($owner, ['status' => 'in_progress']);
        $this->caseFor($owner, ['status' => 'resolved']);
        $this->caseFor($owner, ['status' => 'closed']);

        $this->actingAs($owner);
        /** @var ModuleAggregateSummaryProvider $provider */
        $provider = app($instance->module->summaryProvider);
        $summary = $provider->summarize($instance, $owner);

        $this->assertSame(4, $summary->total);
        $this->assertSame(2, $summary->active);
    }

    public function test_ticket_analytics_are_instance_and_range_scoped_with_timezone_buckets(): void
    {
        $owner = User::factory()->create();
        $registry = app(ModuleRegistry::class);
        $instance = $registry->instance('apes-cic', 'tickets');
        $this->ticketFor($owner, [
            'priority' => 'urgent',
            'created_at' => Carbon::parse('2026-07-31 23:30:00', 'UTC'),
            'updated_at' => Carbon::parse('2026-07-31 23:30:00', 'UTC'),
        ]);
        $this->ticketFor($owner, [
            'status' => 'closed',
            'assigned_to' => $owner->id,
            'created_at' => Carbon::parse('2026-08-01 23:30:00', 'UTC'),
            'updated_at' => Carbon::parse('2026-08-02 00:30:00', 'UTC'),
            'closed_at' => Carbon::parse('2026-08-02 00:30:00', 'UTC'),
        ]);
        $this->ticketFor($owner, [
            'status' => 'closed',
            'created_at' => Carbon::parse('2026-07-31 22:00:00', 'UTC'),
            'updated_at' => Carbon::parse('2026-08-01 01:00:00', 'UTC'),
            'closed_at' => Carbon::parse('2026-08-01 01:00:00', 'UTC'),
        ]);
        $this->ticketFor($owner, [
            'sub_core_key' => 'another-sub-core',
            'created_at' => Carbon::parse('2026-08-01 12:00:00', 'UTC'),
        ]);
        $this->ticketFor($owner, [
            'created_at' => Carbon::parse('2026-08-03 23:00:00', 'UTC'),
        ]);

        /** @var ModuleAnalyticsProvider $provider */
        $provider = app($instance->module->analyticsProvider);
        $snapshot = $provider->snapshot(
            $instance,
            Carbon::parse('2026-08-01 00:00:00', 'Europe/London'),
            Carbon::parse('2026-08-04 00:00:00', 'Europe/London'),
            'Europe/London',
        );

        $this->assertSame(2, $snapshot->total);
        $this->assertSame(1, $snapshot->open);
        $this->assertSame(1, $snapshot->highOrUrgent);
        $this->assertSame(1, $snapshot->unassigned);
        $this->assertSame([
            '2026-08-01' => 1,
            '2026-08-02' => 1,
        ], $snapshot->createdPerDay);
        $this->assertSame([
            '2026-08-01' => 1,
            '2026-08-02' => 1,
        ], $snapshot->closedPerDay);
        $this->assertSame(120.0, $snapshot->medianClosureMinutes);
        $this->assertSame(2, $snapshot->closureSampleSize);
    }

    public function test_apes_cic_hub_activity_excludes_internal_update_content_for_owners(): void
    {
        $owner = User::factory()->create();
        $case = $this->caseFor($owner);
        CaseUpdate::create([
            'shelter_case_id' => $case->id,
            'user_id' => $owner->id,
            'body' => 'Public case progress',
            'visibility' => 'public',
        ]);
        CaseUpdate::create([
            'shelter_case_id' => $case->id,
            'user_id' => $owner->id,
            'body' => 'Private internal case note',
            'visibility' => 'internal',
        ]);

        $this->actingAs($owner)
            ->get(route('apes-cic.index'))
            ->assertOk()
            ->assertSee('Cases')
            ->assertSee('General APES CIC case')
            ->assertDontSee('Public case progress')
            ->assertDontSee('Private internal case note');
    }

    public function test_dashboard_includes_visible_apes_cic_cases_in_totals_and_attention(): void
    {
        $owner = User::factory()->create();
        $case = $this->caseFor($owner, ['title' => 'Membership support needed']);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-module-instance="apes-cic:cases"', false)
            ->assertSee('Membership support needed')
            ->assertSee(route('apes-cic.cases.show', $case));
    }

    public function test_dashboard_excludes_tickets_from_other_sub_cores(): void
    {
        $owner = User::factory()->create();
        $apesTicket = $this->ticketFor($owner, [
            'subject' => 'Visible APES ticket',
        ]);
        $this->ticketFor($owner, [
            'sub_core_key' => 'another-sub-core',
            'subject' => 'Foreign private ticket',
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($apesTicket->subject)
            ->assertDontSee('Foreign private ticket');
    }

    public function test_dashboard_excludes_owned_module_records_without_a_view_capability(): void
    {
        $owner = User::factory()->create();
        $ticket = $this->ticketFor($owner, [
            'subject' => 'Permission-gated private ticket',
        ]);
        $role = Role::query()
            ->where('guard_name', 'web')
            ->where('name', AuthorizationProfile::ROLE_SERVICE_USER)
            ->firstOrFail();
        $permission = Permission::query()
            ->where('guard_name', 'web')
            ->where('name', 'apes-cic.tickets.view-own')
            ->firstOrFail();
        $role->permissions()->detach($permission->id);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($owner->fresh())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee($ticket->subject);
    }

    public function test_hub_merges_only_the_five_latest_accessible_instance_records(): void
    {
        Carbon::setTestNow('2026-08-10 12:00:00');
        $owner = User::factory()->create();
        foreach (range(1, 3) as $offset) {
            $this->ticketFor($owner, [
                'subject' => "Ticket activity {$offset}",
                'created_at' => now()->subMinutes($offset * 2),
                'updated_at' => now()->subMinutes($offset * 2),
            ]);
            $this->caseFor($owner, [
                'title' => "Case activity {$offset}",
                'created_at' => now()->subMinutes(($offset * 2) - 1),
                'updated_at' => now()->subMinutes(($offset * 2) - 1),
            ]);
        }
        $this->ticketFor($owner, [
            'sub_core_key' => 'another-sub-core',
            'subject' => 'Foreign activity title',
            'created_at' => now()->addMinute(),
            'updated_at' => now()->addMinute(),
        ]);

        $response = $this->actingAs($owner)->get(route('apes-cic.index'));

        $response->assertOk()
            ->assertSee('Ticket activity 1')
            ->assertSee('Case activity 1')
            ->assertSee('Ticket activity 2')
            ->assertSee('Case activity 2')
            ->assertSee('Case activity 3')
            ->assertDontSee('Ticket activity 3')
            ->assertDontSee('Foreign activity title');
        $this->assertSame(
            5,
            substr_count($response->getContent(), 'class="attention-item"'),
        );
    }

    public function test_disabled_module_is_absent_from_hub_cards_and_activity(): void
    {
        $owner = User::factory()->create();
        $this->ticketFor($owner, ['subject' => 'Enabled ticket activity']);
        $this->caseFor($owner, ['title' => 'Disabled case activity']);
        ModuleInstallation::query()
            ->where('sub_core_key', 'apes-cic')
            ->where('module_key', 'cases')
            ->update([
                'enabled' => false,
                'disabled_at' => now(),
                'updated_at' => now(),
            ]);

        $this->actingAs($owner)
            ->get(route('apes-cic.index'))
            ->assertOk()
            ->assertSee('data-module-instance="apes-cic:tickets"', false)
            ->assertDontSee('data-module-instance="apes-cic:cases"', false)
            ->assertSee('Enabled ticket activity')
            ->assertDontSee('Disabled case activity');
    }

    public function test_empty_analytics_snapshots_report_no_closure_sample(): void
    {
        $registry = app(ModuleRegistry::class);

        foreach (['tickets', 'cases'] as $moduleKey) {
            $instance = $registry->instance('apes-cic', $moduleKey);
            /** @var ModuleAnalyticsProvider $provider */
            $provider = app($instance->module->analyticsProvider);
            $snapshot = $provider->snapshot(
                $instance,
                Carbon::parse('2026-08-01 00:00:00', 'Europe/London'),
                Carbon::parse('2026-08-02 00:00:00', 'Europe/London'),
                'Europe/London',
            );

            $this->assertSame(0, $snapshot->total);
            $this->assertSame(0, $snapshot->closureSampleSize);
            $this->assertNull($snapshot->medianClosureMinutes);
        }
    }

    private function caseFor(User $owner, array $attributes = []): ShelterCase
    {
        $timestamps = array_intersect_key(
            $attributes,
            array_flip(['created_at', 'updated_at']),
        );
        $case = ShelterCase::create(array_merge([
            'sub_core_key' => 'apes-cic',
            'user_id' => $owner->id,
            'category' => 'general',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'General APES CIC case',
            'details' => null,
            'opened_at' => now(),
        ], $attributes));

        if ($timestamps !== []) {
            $case->timestamps = false;
            $case->forceFill($timestamps)->saveQuietly();
            $case->timestamps = true;
        }

        return $case->fresh();
    }

    private function ticketFor(User $owner, array $attributes = []): SupportTicket
    {
        $timestamps = array_intersect_key(
            $attributes,
            array_flip(['created_at', 'updated_at']),
        );
        $ticket = SupportTicket::create(array_merge([
            'sub_core_key' => SupportTicket::SUB_CORE_APES_CIC,
            'user_id' => $owner->id,
            'service_area' => 'operations',
            'subject' => 'General APES CIC ticket',
            'priority' => 'medium',
            'status' => 'open',
            'description' => 'Ticket analytics fixture.',
        ], $attributes));

        if ($timestamps !== []) {
            $ticket->timestamps = false;
            $ticket->forceFill($timestamps)->saveQuietly();
            $ticket->timestamps = true;
        }

        return $ticket->fresh();
    }
}
