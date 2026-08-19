<?php

namespace Tests\Feature;

use App\Models\StaffProfile;
use App\Models\User;
use App\Services\AuthorizationProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StaffProfileSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_profiles_table_and_user_relation_are_available(): void
    {
        $staff = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_STAFF)
            ->create();

        $this->assertTrue(Schema::hasColumns('staff_profiles', [
            'user_id',
            'job_title',
            'team',
            'work_phone',
            'photo_path',
        ]));

        $profile = $staff->staffProfile()->create([
            'job_title' => 'Rescue coordinator',
            'team' => StaffProfile::TEAM_SHELTER_RESCUE,
            'work_phone' => '+447400123456',
        ]);

        $this->assertInstanceOf(StaffProfile::class, $staff->refresh()->staffProfile);
        $this->assertSame('Rescue coordinator', $profile->job_title);
        $this->assertSame(StaffProfile::TEAM_SHELTER_RESCUE, $profile->team);
        $this->assertSame('+447400123456', $profile->work_phone);
    }

    public function test_cutover_converts_hybrid_users_to_staff_only_and_backfills_staff_profiles(): void
    {
        $public = User::factory()->create([
            'identity_type' => User::IDENTITY_LOCAL,
        ]);
        $hybrid = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_STAFF)
            ->create([
                'identity_type' => User::IDENTITY_HYBRID,
                'oidc_sub' => 'hybrid-subject',
                'password' => 'password',
            ]);
        $passwordHash = $hybrid->password;
        $cloudronStaff = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_ADMINISTRATOR)
            ->cloudronIdentity('admin-subject')
            ->create();

        $migration = require database_path(
            'migrations/2026_08_19_000000_create_staff_profiles_and_split_identities.php',
        );
        $migration->down();

        $this->assertFalse(Schema::hasTable('staff_profiles'));
        DB::table('users')->where('id', $hybrid->id)->update([
            'identity_type' => User::IDENTITY_HYBRID,
            'oidc_sub' => 'hybrid-subject',
        ]);

        $migration->up();

        $hybrid->refresh();
        $this->assertSame(User::IDENTITY_CLOUDRON_OIDC, $hybrid->identity_type);
        $this->assertSame('hybrid-subject', $hybrid->oidc_sub);
        $this->assertSame($passwordHash, $hybrid->password);
        $this->assertDatabaseHas('staff_profiles', ['user_id' => $hybrid->id]);
        $this->assertDatabaseHas('staff_profiles', ['user_id' => $cloudronStaff->id]);
        $this->assertDatabaseMissing('staff_profiles', ['user_id' => $public->id]);
        $this->assertSame(User::IDENTITY_LOCAL, $public->refresh()->identity_type);
    }
}
