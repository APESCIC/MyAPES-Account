<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\User;
use App\Services\ModuleInstallationSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicTicketSaveFlashTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(ModuleInstallationSynchronizer::class)->synchronize();
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: array<string, string>, 3: string}>
     */
    public static function publicTicketRoutes(): array
    {
        return [
            'apes cic' => [
                'apes-cic.tickets.store',
                'apes-cic.tickets.show',
                [
                    'service_area' => 'operations_facilities',
                    'sub_category' => 'premises',
                ],
                'apes-cic',
            ],
            'shelter' => [
                'shelter.tickets.store',
                'shelter.tickets.show',
                [
                    'service_area' => 'rescue',
                ],
                'shelter-rescue',
            ],
            'pet care' => [
                'petcare.tickets.store',
                'petcare.tickets.show',
                [
                    'service_area' => 'appointment',
                ],
                'pet-care-clinic',
            ],
        ];
    }

    /**
     * @param  array<string, string>  $categoryFields
     */
    #[DataProvider('publicTicketRoutes')]
    public function test_public_ticket_create_shows_a_success_flash(
        string $storeRoute,
        string $showRoute,
        array $categoryFields,
        string $subCoreKey,
    ): void {
        Notification::fake();
        $owner = User::factory()->create();

        $response = $this->actingAs($owner)
            ->post(route($storeRoute), [
                ...$categoryFields,
                'subject' => 'Public ticket create flash',
                'priority' => 'medium',
                'description' => 'Need a success flash after create.',
            ]);

        $ticket = SupportTicket::query()
            ->where('subject', 'Public ticket create flash')
            ->firstOrFail();

        $response->assertRedirect(route($showRoute, $ticket))
            ->assertSessionHas('status', 'Your ticket has been saved.');

        $this->assertSame($owner->id, $ticket->user_id);
        $this->assertSame($subCoreKey, $ticket->sub_core_key);

        $this->get(route($showRoute, $ticket))
            ->assertOk()
            ->assertSee('Your ticket has been saved.')
            ->assertDontSee('Ticket created.', false);
    }

    /**
     * @param  array<string, string>  $categoryFields
     */
    #[DataProvider('publicTicketRoutes')]
    public function test_public_ticket_comment_shows_a_success_flash(
        string $storeRoute,
        string $showRoute,
        array $categoryFields,
        string $subCoreKey,
    ): void {
        Notification::fake();
        $owner = User::factory()->create();
        $ticket = SupportTicket::query()->create([
            'sub_core_key' => $subCoreKey,
            'user_id' => $owner->id,
            'service_area' => $categoryFields['service_area'],
            'sub_category' => $categoryFields['sub_category'] ?? null,
            'subject' => 'Public ticket comment flash',
            'priority' => 'low',
            'status' => 'open',
            'description' => 'Existing ticket waiting for an owner update.',
        ]);
        $updateRoute = str_replace('.store', '.update', $storeRoute);

        $this->actingAs($owner)
            ->put(route($updateRoute, $ticket), [
                'message' => 'Here is my follow-up.',
            ])
            ->assertRedirect(route($showRoute, $ticket))
            ->assertSessionHas('status', 'Your update has been saved.');

        $this->assertDatabaseHas('support_ticket_messages', [
            'support_ticket_id' => $ticket->id,
            'user_id' => $owner->id,
            'message' => 'Here is my follow-up.',
            'is_staff_note' => false,
        ]);

        $this->get(route($showRoute, $ticket))
            ->assertOk()
            ->assertSee('Your update has been saved.')
            ->assertDontSee('Ticket updated.');
    }
}
