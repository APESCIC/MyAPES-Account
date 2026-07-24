<?php

namespace Database\Seeders;

use App\Models\PetCareConsultation;
use App\Models\PetProfile;
use App\Models\ShelterCase;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LocalQaSeeder extends Seeder
{
    use WithoutModelEvents;

    public const SERVICE_USER_EMAIL = 'qa.service.user@myapes.local';

    public const STAFF_EMAIL = 'qa.staff@myapes.local';

    public const ADMIN_EMAIL = 'qa.admin@myapes.local';

    public const SUPERADMIN_EMAIL = 'qa.superadmin@myapes.local';

    public const DEFAULT_PASSWORD = 'MyAPES-Local-QA-2026!';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $seededAt = CarbonImmutable::parse('2026-01-15 09:00:00', 'UTC');

        $serviceUser = $this->upsertUser(
            self::SERVICE_USER_EMAIL,
            'QA Service User',
            User::ROLE_SERVICE_USER,
            null,
            [],
            $seededAt
        );
        $staffUser = $this->upsertUser(
            self::STAFF_EMAIL,
            'QA Staff User',
            User::ROLE_STAFF,
            'local-qa-staff',
            ['position.staff'],
            $seededAt
        );
        $adminUser = $this->upsertUser(
            self::ADMIN_EMAIL,
            'QA Admin User',
            User::ROLE_ADMIN,
            'local-qa-admin',
            ['admin'],
            $seededAt
        );
        $superAdminUser = $this->upsertUser(
            self::SUPERADMIN_EMAIL,
            'QA Superadmin User',
            User::ROLE_SUPERADMIN,
            'local-qa-superadmin',
            ['superadmin'],
            $seededAt
        );

        $this->upsertProfile($serviceUser, 'Service User', '+44 7700 900101', 'APES CIC Service User');
        $this->upsertProfile($staffUser, 'Staff User', '+44 7700 900102', 'APES CIC Staff');
        $this->upsertProfile($adminUser, 'Admin User', '+44 7700 900103', 'APES CIC Admin');
        $this->upsertProfile($superAdminUser, 'Superadmin User', '+44 7700 900104', 'APES CIC Superadmin');

        $openTicket = SupportTicket::query()->updateOrCreate(
            ['user_id' => $serviceUser->id, 'subject' => 'QA Seed: IT account support request'],
            [
                'assigned_to' => $staffUser->id,
                'service_area' => 'it',
                'priority' => 'high',
                'status' => 'in_progress',
                'description' => 'Service user cannot access a shared mailbox from home.',
                'closed_at' => null,
            ]
        );
        $openTicket->messages()->delete();
        $openTicket->messages()->createMany([
            [
                'user_id' => $serviceUser->id,
                'message' => 'Initial issue report for remote mailbox access.',
                'is_staff_note' => false,
            ],
            [
                'user_id' => $staffUser->id,
                'message' => 'Investigating SMTP relay and mailbox permissions.',
                'is_staff_note' => true,
            ],
        ]);

        $closedTicket = SupportTicket::query()->updateOrCreate(
            ['user_id' => $serviceUser->id, 'subject' => 'QA Seed: HR policy clarification'],
            [
                'assigned_to' => $adminUser->id,
                'service_area' => 'human_resources',
                'priority' => 'medium',
                'status' => 'resolved',
                'description' => 'Clarification requested on volunteer rota policy updates.',
                'closed_at' => $seededAt->subDay(),
            ]
        );
        $closedTicket->messages()->delete();
        $closedTicket->messages()->createMany([
            [
                'user_id' => $serviceUser->id,
                'message' => 'Please confirm the rota policy for evenings.',
                'is_staff_note' => false,
            ],
            [
                'user_id' => $adminUser->id,
                'message' => 'Policy shared and confirmed with service user.',
                'is_staff_note' => true,
            ],
        ]);

        $shelterPet = PetProfile::query()->updateOrCreate(
            [
                'user_id' => $serviceUser->id,
                'service_domain' => PetProfile::DOMAIN_SHELTER,
                'name' => 'Mango',
            ],
            [
                'species' => 'Cockatiel',
                'age_years' => 4,
                'sex' => 'female',
                'neutering_status' => 'unknown',
                'health_issues' => 'Mild feather plucking when stressed.',
                'photo_path' => null,
            ]
        );

        $petCarePet = PetProfile::query()->updateOrCreate(
            [
                'user_id' => $serviceUser->id,
                'service_domain' => PetProfile::DOMAIN_PETCARE,
                'name' => 'Pico',
            ],
            [
                'species' => 'African Grey',
                'age_years' => 7,
                'sex' => 'male',
                'neutering_status' => 'unknown',
                'health_issues' => 'Requires periodic beak health checks.',
                'photo_path' => null,
            ]
        );

        ShelterCase::query()->updateOrCreate(
            ['pet_profile_id' => $shelterPet->id, 'title' => 'QA Seed: Mango rescue intake'],
            [
                'user_id' => $serviceUser->id,
                'assigned_to' => $staffUser->id,
                'case_type' => 'rescue',
                'status' => 'in_review',
                'details' => 'Assessing behaviour and temporary foster placement.',
                'closed_at' => null,
            ]
        );

        ShelterCase::query()->updateOrCreate(
            ['pet_profile_id' => $shelterPet->id, 'title' => 'QA Seed: Mango adoption follow-up'],
            [
                'user_id' => $serviceUser->id,
                'assigned_to' => $adminUser->id,
                'case_type' => 'adoption',
                'status' => 'closed',
                'details' => 'Home check and post-placement follow-up completed.',
                'closed_at' => $seededAt->subDays(2),
            ]
        );

        PetCareConsultation::query()->updateOrCreate(
            ['pet_profile_id' => $petCarePet->id, 'subject' => 'QA Seed: Pico annual wellness consult'],
            [
                'user_id' => $serviceUser->id,
                'assigned_to' => $staffUser->id,
                'status' => 'in_progress',
                'notes' => 'Diet review and behavioural enrichment plan in progress.',
                'scheduled_for' => $seededAt->addDays(2),
                'closed_at' => null,
            ]
        );

        PetCareConsultation::query()->updateOrCreate(
            ['pet_profile_id' => $petCarePet->id, 'subject' => 'QA Seed: Pico beak health follow-up'],
            [
                'user_id' => $serviceUser->id,
                'assigned_to' => $adminUser->id,
                'status' => 'closed',
                'notes' => 'Follow-up completed with maintenance advice provided.',
                'scheduled_for' => $seededAt->subDays(4),
                'closed_at' => $seededAt->subDays(3),
            ]
        );
    }

    private function upsertUser(
        string $email,
        string $name,
        string $role,
        ?string $oidcSub,
        array $ldapGroups,
        CarbonImmutable $seededAt
    ): User {
        /** @var User $user */
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => self::DEFAULT_PASSWORD,
                'role' => $role,
                'oidc_sub' => $oidcSub,
                'ldap_groups' => $ldapGroups,
                'email_verified_at' => $seededAt,
            ]
        );

        return $user;
    }

    private function upsertProfile(User $user, string $preferredName, string $phone, string $organization): void
    {
        UserProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'preferred_name' => $preferredName,
                'phone' => $phone,
                'organization' => $organization,
                'support_needs' => 'QA seed profile for local testing.',
                'avatar_path' => null,
            ]
        );
    }
}
