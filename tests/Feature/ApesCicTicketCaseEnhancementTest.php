<?php

namespace Tests\Feature;

use App\Models\ModuleSetting;
use App\Models\ShelterCase;
use App\Models\SupportAttachment;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\AuthorizationProfile;
use App\Services\ModuleInstallationSynchronizer;
use App\Services\ModuleSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApesCicTicketCaseEnhancementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ModuleInstallationSynchronizer::class)->synchronize();
        Storage::fake('public');
    }

    public function test_ticket_creation_requires_website_for_web_subcategories_and_stores_attachments(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)
            ->post(route('apes-cic.tickets.store'), [
                'service_area' => 'web_development',
                'sub_category' => 'website_issue',
                'subject' => 'Homepage broken',
                'priority' => 'high',
                'description' => 'The homepage layout is broken on mobile.',
            ])
            ->assertSessionHasErrors('affected_website_key');

        $response = $this->actingAs($owner)
            ->post(route('apes-cic.tickets.store'), [
                'service_area' => 'web_development',
                'sub_category' => 'website_issue',
                'affected_website_key' => 'apes_org_uk',
                'subject' => 'Homepage broken',
                'priority' => 'high',
                'description' => 'The homepage layout is broken on mobile.',
                'screenshots' => [
                    UploadedFile::fake()->image('issue.png', 640, 480),
                ],
            ]);

        $ticket = SupportTicket::query()
            ->where('subject', 'Homepage broken')
            ->firstOrFail();
        $response->assertRedirect(route('apes-cic.tickets.show', $ticket));
        $this->assertSame('web_development', $ticket->service_area);
        $this->assertSame('website_issue', $ticket->sub_category);
        $this->assertSame('apes_org_uk', $ticket->affected_website_key);
        $this->assertSame(1, $ticket->attachments()->count());
        $this->assertSame('screenshot', $ticket->attachments()->first()->kind);
    }

    public function test_data_access_case_requires_website_and_accepts_evidence(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)
            ->post(route('apes-cic.cases.store'), [
                'category' => 'data_access_request',
                'sub_category' => 'copy_of_data',
                'priority' => 'high',
                'title' => 'SAR for my account',
                'details' => 'Please provide a copy of my personal data.',
            ])
            ->assertSessionHasErrors('affected_website_key');

        $response = $this->actingAs($owner)
            ->post(route('apes-cic.cases.store'), [
                'category' => 'data_access_request',
                'sub_category' => 'copy_of_data',
                'affected_website_key' => 'myapes_account',
                'priority' => 'high',
                'title' => 'SAR for my account',
                'details' => 'Please provide a copy of my personal data.',
                'screenshots' => [
                    UploadedFile::fake()->image('evidence.jpg', 800, 600),
                ],
            ]);

        $case = ShelterCase::query()
            ->where('title', 'SAR for my account')
            ->firstOrFail();
        $response->assertRedirect(route('apes-cic.cases.show', $case));
        $this->assertSame('data_access_request', $case->category);
        $this->assertSame('copy_of_data', $case->sub_category);
        $this->assertSame(1, SupportAttachment::query()->where('attachable_id', $case->id)->count());
    }

    public function test_staff_can_reassign_ticket_owner_and_assignee(): void
    {
        $owner = User::factory()->create();
        $newOwner = User::factory()->create(['name' => 'New Ticket Owner']);
        $staff = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_STAFF)
            ->create();
        $assignee = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_STAFF)
            ->create(['name' => 'Assignee Staff']);

        $ticket = SupportTicket::create([
            'sub_core_key' => 'apes-cic',
            'user_id' => $owner->id,
            'service_area' => 'operations_facilities',
            'sub_category' => 'premises',
            'subject' => 'Ownership reassignment',
            'priority' => 'medium',
            'status' => 'open',
            'description' => 'Needs a new owner.',
        ]);

        $this->actingAs($staff)
            ->put(route('apes-cic.tickets.update', $ticket), [
                'user_id' => $newOwner->id,
                'assigned_to' => $assignee->id,
            ])
            ->assertRedirect(route('apes-cic.tickets.show', $ticket));

        $ticket->refresh();
        $this->assertSame($newOwner->id, $ticket->user_id);
        $this->assertSame($assignee->id, $ticket->assigned_to);
    }

    public function test_module_settings_seed_on_sync_and_super_admin_can_save(): void
    {
        $this->assertDatabaseHas('module_settings', [
            'sub_core_key' => 'apes-cic',
            'module_key' => 'tickets',
        ]);
        $this->assertDatabaseHas('module_settings', [
            'sub_core_key' => 'apes-cic',
            'module_key' => 'cases',
        ]);

        $superAdmin = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_SUPER_ADMIN)
            ->create();
        $record = ModuleSetting::query()
            ->where('sub_core_key', 'apes-cic')
            ->where('module_key', 'tickets')
            ->firstOrFail();
        $settings = $record->settings;
        $settings['websites'][0]['label'] = 'APES main website (edited)';

        $this->actingAs($superAdmin)
            ->put(route('admin.modules.settings.update', ['apes-cic', 'tickets']), [
                'version' => $record->lock_version,
                'websites' => $settings['websites'],
                'service_areas' => $settings['service_areas'],
            ])
            ->assertRedirect(route('admin.modules.settings.edit', ['apes-cic', 'tickets']));

        $this->assertSame(
            'APES main website (edited)',
            app(ModuleSettingsService::class)->get('apes-cic', 'tickets')['websites'][0]['label'],
        );
    }

    public function test_admin_modules_page_shows_grid_and_settings_links(): void
    {
        $superAdmin = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_SUPER_ADMIN)
            ->create();

        $response = $this->actingAs($superAdmin)->get(route('admin.modules.index'));
        $response->assertOk();
        $response->assertSee('module-registry__rows', false);
        $response->assertSee(route('admin.modules.settings.edit', ['apes-cic', 'tickets']));
        $response->assertSee(route('admin.modules.settings.edit', ['apes-cic', 'cases']));
        $response->assertSeeText('Settings');
    }

    public function test_ticket_and_case_indexes_surface_owner_and_assigned_columns(): void
    {
        $owner = User::factory()->create(['name' => 'Case Owner Visible']);
        $staff = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_STAFF)
            ->create(['name' => 'Assigned Staff Visible']);

        SupportTicket::create([
            'sub_core_key' => 'apes-cic',
            'user_id' => $owner->id,
            'assigned_to' => $staff->id,
            'service_area' => 'it_systems',
            'sub_category' => 'account_access',
            'subject' => 'Index ownership ticket',
            'priority' => 'low',
            'status' => 'open',
            'description' => 'Show owner columns.',
        ]);
        ShelterCase::create([
            'sub_core_key' => 'apes-cic',
            'user_id' => $owner->id,
            'assigned_to' => $staff->id,
            'category' => 'formal_complaint',
            'sub_category' => 'service_complaint',
            'priority' => 'medium',
            'status' => 'open',
            'title' => 'Index ownership case',
            'details' => 'Show owner columns.',
            'opened_at' => now(),
        ]);

        $this->actingAs($staff)
            ->get(route('apes-cic.tickets.index'))
            ->assertOk()
            ->assertSee('Assigned')
            ->assertSee('Case Owner Visible')
            ->assertSee('Assigned Staff Visible');
        $this->get(route('apes-cic.cases.index'))
            ->assertOk()
            ->assertSee('Assigned')
            ->assertSee('Case Owner Visible')
            ->assertSee('Formal casework including data access');
    }
}
