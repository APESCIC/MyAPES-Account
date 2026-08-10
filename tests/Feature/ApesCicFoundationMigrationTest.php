<?php

namespace Tests\Feature;

use App\Models\ShelterCase;
use App\Models\SupportTicket;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApesCicFoundationMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::disableForeignKeyConstraints();
        foreach ([
            'case_updates',
            'audit_logs',
            'notifications',
            'shelter_cases',
            'pet_profiles',
            'support_ticket_messages',
            'support_tickets',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();
        $this->createLegacySchema();
    }

    public function test_v0121_records_and_references_are_preserved_by_the_v013_foundation(): void
    {
        $this->seedLegacyRecords();

        $this->foundationMigration()->up();

        $this->assertDatabaseHas('support_tickets', [
            'id' => 40,
            'sub_core_key' => SupportTicket::SUB_CORE_APES_CIC,
            'user_id' => 10,
            'assigned_to' => 20,
            'service_area' => 'operations',
            'status' => 'resolved',
            'closed_at' => '2026-07-25 12:00:00',
        ]);
        $this->assertDatabaseHas('support_ticket_messages', [
            'id' => 50,
            'support_ticket_id' => 40,
            'user_id' => 10,
            'message' => 'Legacy ticket message',
        ]);
        $this->assertDatabaseHas('shelter_cases', [
            'id' => 60,
            'sub_core_key' => ShelterCase::SUB_CORE_SHELTER_RESCUE,
            'pet_profile_id' => 30,
            'user_id' => 10,
            'assigned_to' => 20,
            'case_type' => 'rescue',
            'status' => 'closed',
            'opened_at' => '2026-07-24 10:00:00',
            'resolved_at' => null,
            'closed_at' => '2026-07-26 15:00:00',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'id' => 70,
            'auditable_type' => SupportTicket::class,
            'auditable_id' => 40,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'id' => 71,
            'auditable_type' => ShelterCase::class,
            'auditable_id' => 60,
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => '00000000-0000-4000-8000-000000000012',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => 10,
        ]);

        DB::table('support_tickets')->insert([
            'id' => 41,
            'user_id' => 10,
            'assigned_to' => null,
            'service_area' => 'it',
            'subject' => 'Legacy writer after migration',
            'priority' => 'medium',
            'status' => 'open',
            'description' => 'Uses only the v0.12.1 ticket fields.',
            'closed_at' => null,
            'created_at' => '2026-08-10 10:00:00',
            'updated_at' => '2026-08-10 10:00:00',
        ]);
        DB::table('shelter_cases')->insert([
            'id' => 61,
            'pet_profile_id' => 30,
            'user_id' => 10,
            'assigned_to' => null,
            'case_type' => 'adoption',
            'status' => 'in_review',
            'title' => 'Legacy Shelter writer after migration',
            'details' => null,
            'closed_at' => null,
            'created_at' => '2026-08-10 11:00:00',
            'updated_at' => '2026-08-10 11:00:00',
        ]);

        $this->assertDatabaseHas('support_tickets', [
            'id' => 41,
            'sub_core_key' => SupportTicket::SUB_CORE_APES_CIC,
        ]);
        $this->assertDatabaseHas('shelter_cases', [
            'id' => 61,
            'sub_core_key' => ShelterCase::SUB_CORE_SHELTER_RESCUE,
            'case_type' => 'adoption',
            'status' => 'in_review',
        ]);
    }

    public function test_partial_foundation_converges_and_a_completed_migration_is_retryable(): void
    {
        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->string('sub_core_key', 64)->nullable()->after('id');
        });
        Schema::table('shelter_cases', function (Blueprint $table): void {
            $table->string('sub_core_key', 64)->nullable()->after('id');
            $table->string('category')->nullable()->after('case_type');
            $table->string('priority', 16)->nullable()->after('category');
            $table->timestamp('opened_at')->nullable()->after('details');
            $table->timestamp('resolved_at')->nullable()->after('opened_at');
        });
        Schema::create('case_updates', function (Blueprint $table): void {
            $table->id();
        });

        $migration = $this->foundationMigration();
        $migration->up();
        $migration->up();

        $this->assertTrue(Schema::hasIndex(
            'support_tickets',
            'support_tickets_sub_core_status_index',
        ));
        $this->assertTrue(Schema::hasIndex(
            'support_tickets',
            'support_tickets_sub_core_owner_index',
        ));
        foreach ([
            'shelter_cases_sub_core_status_index',
            'shelter_cases_sub_core_owner_index',
            'shelter_cases_sub_core_assignee_status_index',
        ] as $index) {
            $this->assertTrue(Schema::hasIndex('shelter_cases', $index));
        }
        $this->assertTrue(Schema::hasColumns('case_updates', [
            'shelter_case_id',
            'user_id',
            'body',
            'visibility',
            'created_at',
            'updated_at',
        ]));
        $this->assertTrue(Schema::hasIndex(
            'case_updates',
            'case_updates_case_visibility_created_index',
        ));

        $caseColumns = collect(Schema::getColumns('shelter_cases'))->keyBy('name');
        $this->assertTrue($caseColumns->get('pet_profile_id')['nullable']);
        $this->assertTrue($caseColumns->get('case_type')['nullable']);
        $this->assertSame(
            'open',
            trim((string) $caseColumns->get('status')['default'], "'\""),
        );
        $ticketColumns = collect(Schema::getColumns('support_tickets'))->keyBy('name');
        $this->assertFalse($ticketColumns->get('sub_core_key')['nullable']);
        $this->assertSame(
            SupportTicket::SUB_CORE_APES_CIC,
            trim((string) $ticketColumns->get('sub_core_key')['default'], "'\""),
        );
    }

    public function test_case_update_defaults_and_deletion_constraints_are_explicit(): void
    {
        $this->seedLegacyRecords();
        $this->foundationMigration()->up();

        DB::table('case_updates')->insert([
            'id' => 80,
            'shelter_case_id' => 60,
            'user_id' => 20,
            'body' => 'Constraint fixture',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->assertDatabaseHas('case_updates', [
            'id' => 80,
            'visibility' => 'public',
            'user_id' => 20,
        ]);

        DB::table('users')->where('id', 20)->delete();
        $this->assertDatabaseHas('case_updates', [
            'id' => 80,
            'user_id' => null,
        ]);

        DB::table('shelter_cases')->where('id', 60)->delete();
        $this->assertDatabaseMissing('case_updates', ['id' => 80]);
    }

    private function createLegacySchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });
        Schema::create('support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('service_area');
            $table->string('subject');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->text('description');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['service_area', 'status']);
        });
        Schema::create('support_ticket_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->boolean('is_staff_note')->default(false);
            $table->timestamps();
        });
        Schema::create('pet_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('service_domain', ['shelter', 'petcare']);
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('shelter_cases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pet_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('case_type', ['adoption', 'surrender', 'rescue', 'fostering']);
            $table->enum('status', ['open', 'in_review', 'closed'])->default('open');
            $table->string('title');
            $table->text('details')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['case_type', 'status']);
        });
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('event');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    private function seedLegacyRecords(): void
    {
        DB::table('users')->insert([
            ['id' => 10, 'name' => 'Owner', 'email' => 'owner@example.test', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 20, 'name' => 'Assignee', 'email' => 'assignee@example.test', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('pet_profiles')->insert([
            'id' => 30,
            'user_id' => 10,
            'service_domain' => 'shelter',
            'name' => 'Legacy pet',
            'created_at' => '2026-07-23 09:00:00',
            'updated_at' => '2026-07-23 09:00:00',
        ]);
        DB::table('support_tickets')->insert([
            'id' => 40,
            'user_id' => 10,
            'assigned_to' => 20,
            'service_area' => 'operations',
            'subject' => 'Legacy ticket',
            'priority' => 'high',
            'status' => 'resolved',
            'description' => 'Legacy ticket description',
            'closed_at' => '2026-07-25 12:00:00',
            'created_at' => '2026-07-24 09:00:00',
            'updated_at' => '2026-07-25 12:00:00',
        ]);
        DB::table('support_ticket_messages')->insert([
            'id' => 50,
            'support_ticket_id' => 40,
            'user_id' => 10,
            'message' => 'Legacy ticket message',
            'is_staff_note' => false,
            'created_at' => '2026-07-24 09:05:00',
            'updated_at' => '2026-07-24 09:05:00',
        ]);
        DB::table('shelter_cases')->insert([
            'id' => 60,
            'pet_profile_id' => 30,
            'user_id' => 10,
            'assigned_to' => 20,
            'case_type' => 'rescue',
            'status' => 'closed',
            'title' => 'Legacy Shelter case',
            'details' => 'Legacy case details',
            'closed_at' => '2026-07-26 15:00:00',
            'created_at' => '2026-07-24 10:00:00',
            'updated_at' => '2026-07-26 15:00:00',
        ]);
        DB::table('audit_logs')->insert([
            ['id' => 70, 'user_id' => 10, 'event' => 'ticket.legacy', 'auditable_type' => SupportTicket::class, 'auditable_id' => 40, 'context' => '{}', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 71, 'user_id' => 20, 'event' => 'case.legacy', 'auditable_type' => ShelterCase::class, 'auditable_id' => 60, 'context' => '{}', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('notifications')->insert([
            'id' => '00000000-0000-4000-8000-000000000012',
            'type' => 'LegacyNotification',
            'notifiable_type' => 'App\\Models\\User',
            'notifiable_id' => 10,
            'data' => '{"case_id":60}',
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function foundationMigration(): Migration
    {
        /** @var Migration $migration */
        $migration = require database_path(
            'migrations/2026_08_10_010000_add_apes_cic_ticket_case_foundation.php',
        );

        return $migration;
    }
}
