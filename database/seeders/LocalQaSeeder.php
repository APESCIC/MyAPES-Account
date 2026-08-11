<?php

namespace Database\Seeders;

use App\Models\CaseUpdate;
use App\Models\PetCareConsultation;
use App\Models\PetProfile;
use App\Models\Role;
use App\Models\RoleSource;
use App\Models\ShelterCase;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AuthorizationMetadataSynchronizer;
use App\Services\AuthorizationProfile;
use App\Services\AuthorizationRoleManagementService;
use App\Services\AuthorizationRoleMaterializer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use LogicException;

class LocalQaSeeder extends Seeder
{
    use WithoutModelEvents;

    public const SERVICE_USER_EMAIL = 'qa.service.user@myapes.local';

    public const STAFF_EMAIL = 'qa.staff@myapes.local';

    public const ADMIN_EMAIL = 'qa.admin@myapes.local';

    public const SUPERADMIN_EMAIL = 'qa.superadmin@myapes.local';

    public const CUSTOM_ROLE_NAME = 'local-qa-reviewer';

    public const DEFAULT_PASSWORD = 'MyAPES-Local-QA-2026!';

    /**
     * @return list<string>
     */
    public static function emails(): array
    {
        return [
            self::SERVICE_USER_EMAIL,
            self::STAFF_EMAIL,
            self::ADMIN_EMAIL,
            self::SUPERADMIN_EMAIL,
        ];
    }

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException(
                'Local QA fixtures are unavailable outside local and testing environments.',
            );
        }

        app(AuthorizationMetadataSynchronizer::class)->synchronize();
        $seededAt = CarbonImmutable::parse('2026-07-24 09:00:00', 'Europe/London')->utc();

        $serviceUser = $this->upsertUser(
            self::SERVICE_USER_EMAIL,
            'QA Service User',
            User::ROLE_SERVICE_USER,
            $seededAt
        );
        $staffUser = $this->upsertUser(
            self::STAFF_EMAIL,
            'QA Staff User',
            User::ROLE_STAFF,
            $seededAt
        );
        $adminUser = $this->upsertUser(
            self::ADMIN_EMAIL,
            'QA Admin User',
            User::ROLE_ADMIN,
            $seededAt
        );
        $superAdminUser = $this->upsertUser(
            self::SUPERADMIN_EMAIL,
            'QA Superadmin User',
            User::ROLE_SUPERADMIN,
            $seededAt
        );
        $this->upsertCustomRole($superAdminUser, $staffUser);

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
        $this->setTimestamps($openTicket, $seededAt->addMinutes(40));

        $followUpTicket = SupportTicket::query()->updateOrCreate(
            ['user_id' => $serviceUser->id, 'subject' => 'QA Seed: Documentation follow-up'],
            [
                'assigned_to' => $staffUser->id,
                'service_area' => 'legal',
                'priority' => 'medium',
                'status' => 'open',
                'description' => 'Supporting documents are ready for a final service review.',
                'closed_at' => null,
            ]
        );
        $followUpTicket->messages()->delete();
        $followUpTicket->messages()->createMany([
            [
                'user_id' => $serviceUser->id,
                'message' => 'The requested supporting documents have been uploaded.',
                'is_staff_note' => false,
            ],
            [
                'user_id' => $staffUser->id,
                'message' => 'Ready for final document review.',
                'is_staff_note' => true,
            ],
        ]);
        $this->setTimestamps($followUpTicket, $seededAt->addMinutes(20));

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
        $this->setTimestamps($closedTicket, $seededAt->subDays(3));

        $reopenedApesCase = ShelterCase::query()->updateOrCreate(
            [
                'sub_core_key' => ShelterCase::SUB_CORE_APES_CIC,
                'user_id' => $serviceUser->id,
                'title' => 'QA Seed: Reopened membership support review',
            ],
            [
                'pet_profile_id' => null,
                'assigned_to' => $staffUser->id,
                'case_type' => null,
                'category' => 'membership',
                'priority' => 'high',
                'status' => 'in_progress',
                'details' => 'Additional information reopened this membership support review.',
                'opened_at' => $seededAt->subDays(5),
                'resolved_at' => null,
                'closed_at' => null,
            ],
        );
        $reopenedApesCase->updates()->delete();
        $reopenedUpdates = $reopenedApesCase->updates()->createMany([
            [
                'user_id' => $serviceUser->id,
                'body' => 'Additional information received; case reopened for review.',
                'visibility' => CaseUpdate::VISIBILITY_PUBLIC,
            ],
            [
                'user_id' => $staffUser->id,
                'body' => 'Internal triage note for local visibility testing.',
                'visibility' => CaseUpdate::VISIBILITY_INTERNAL,
            ],
        ]);
        $this->setTimestamps($reopenedUpdates[0], $seededAt->subMinutes(30));
        $this->setTimestamps($reopenedUpdates[1], $seededAt->subMinutes(20));
        $this->setTimestamps($reopenedApesCase, $seededAt->subMinutes(10));

        $waitingApesCase = ShelterCase::query()->updateOrCreate(
            [
                'sub_core_key' => ShelterCase::SUB_CORE_APES_CIC,
                'user_id' => $serviceUser->id,
                'title' => 'QA Seed: Welfare response required',
            ],
            [
                'pet_profile_id' => null,
                'assigned_to' => $staffUser->id,
                'case_type' => null,
                'category' => 'welfare',
                'priority' => 'urgent',
                'status' => 'waiting_on_user',
                'details' => 'Waiting for the service user to confirm the requested welfare information.',
                'opened_at' => $seededAt->subDays(3),
                'resolved_at' => null,
                'closed_at' => null,
            ],
        );
        $waitingApesCase->updates()->delete();
        $this->setTimestamps($waitingApesCase, $seededAt->subMinutes(20));

        $closedApesCase = ShelterCase::query()->updateOrCreate(
            [
                'sub_core_key' => ShelterCase::SUB_CORE_APES_CIC,
                'user_id' => $serviceUser->id,
                'title' => 'QA Seed: Closed operations complaint',
            ],
            [
                'pet_profile_id' => null,
                'assigned_to' => $adminUser->id,
                'case_type' => null,
                'category' => 'complaint',
                'priority' => 'low',
                'status' => 'closed',
                'details' => 'The operations complaint was reviewed and the outcome was shared.',
                'opened_at' => $seededAt->subDays(8),
                'resolved_at' => $seededAt->subDays(3),
                'closed_at' => $seededAt->subDays(2),
            ],
        );
        $closedApesCase->updates()->delete();
        $closedUpdate = $closedApesCase->updates()->create([
            'user_id' => $adminUser->id,
            'body' => 'Outcome shared with the service user.',
            'visibility' => CaseUpdate::VISIBILITY_PUBLIC,
        ]);
        $this->setTimestamps($closedUpdate, $seededAt->subDays(2));
        $this->setTimestamps($closedApesCase, $seededAt->subDays(2));

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

        $rescueCase = ShelterCase::query()->updateOrCreate(
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
        $this->setTimestamps($rescueCase, $seededAt->subMinutes(5));

        $fosterCase = ShelterCase::query()->updateOrCreate(
            ['pet_profile_id' => $shelterPet->id, 'title' => 'QA Seed: Mango foster placement review'],
            [
                'user_id' => $serviceUser->id,
                'assigned_to' => $adminUser->id,
                'case_type' => 'fostering',
                'status' => 'open',
                'details' => 'Confirming a calm temporary placement and transport plan.',
                'closed_at' => null,
            ]
        );
        $this->setTimestamps($fosterCase, $seededAt->subDay()->addHours(3));

        $closedShelterCase = ShelterCase::query()->updateOrCreate(
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
        $this->setTimestamps($closedShelterCase, $seededAt->subDays(2));

        $wellnessConsultation = PetCareConsultation::query()->updateOrCreate(
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
        $this->setTimestamps($wellnessConsultation, $seededAt->subDays(2)->addHours(4));

        $enrichmentConsultation = PetCareConsultation::query()->updateOrCreate(
            ['pet_profile_id' => $petCarePet->id, 'subject' => 'QA Seed: Pico nutrition enrichment review'],
            [
                'user_id' => $serviceUser->id,
                'assigned_to' => $staffUser->id,
                'status' => 'open',
                'notes' => 'Review foraging activities and seasonal food variety.',
                'scheduled_for' => $seededAt->addDays(5),
                'closed_at' => null,
            ]
        );
        $this->setTimestamps($enrichmentConsultation, $seededAt->subDays(3)->addHours(2));

        $closedConsultation = PetCareConsultation::query()->updateOrCreate(
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
        $this->setTimestamps($closedConsultation, $seededAt->subDays(3));
    }

    private function upsertUser(
        string $email,
        string $name,
        string $role,
        CarbonImmutable $seededAt
    ): User {
        /** @var User $user */
        $user = User::query()->firstOrNew(['email' => $email]);
        $user->forceFill([
            'name' => $name,
            'username' => str_replace('@myapes.local', '', $email),
            'password' => self::DEFAULT_PASSWORD,
            'oidc_sub' => null,
            'identity_type' => User::IDENTITY_LOCAL,
            'ldap_groups' => [],
            'email_verified_at' => $seededAt,
            'onboarding_completed_at' => $seededAt,
            'suspended_at' => null,
            'suspended_by' => null,
            'suspension_reason' => null,
        ]);
        $user->setAccessLevel($role)->save();

        $profile = app(AuthorizationProfile::class);
        $protectedRoleName = $profile->protectedRoleForLegacy($role);
        $protectedRole = Role::query()
            ->where('guard_name', 'web')
            ->where('name', $protectedRoleName)
            ->firstOrFail();
        app(AuthorizationRoleMaterializer::class)->grant(
            $user,
            $protectedRole,
            RoleSource::SOURCE_SYSTEM,
        );

        $user->contactPreference()->firstOrCreate([], [
            'confirmed_at' => $seededAt,
            'policy_version' => (string) config('myapes.consent.policy_version'),
        ]);
        foreach (['apes-cic', 'shelter-rescue', 'pet-care-clinic'] as $service) {
            $user->serviceSelections()->firstOrCreate(['sub_core_key' => $service]);
        }

        return $user;
    }

    private function upsertCustomRole(User $actor, User $assignee): void
    {
        $management = app(AuthorizationRoleManagementService::class);
        $role = Role::query()
            ->where('guard_name', 'web')
            ->where('name', self::CUSTOM_ROLE_NAME)
            ->first();
        $permissions = [];

        $role = $management->runAsLocalQa(
            $actor,
            static fn (): Role => $role === null
                ? $management->create(
                    $actor,
                    self::CUSTOM_ROLE_NAME,
                    $permissions,
                )
                : $management->update(
                    $actor,
                    $role,
                    self::CUSTOM_ROLE_NAME,
                    $permissions,
                ),
        );

        app(AuthorizationRoleMaterializer::class)->grant(
            $assignee,
            $role,
            RoleSource::SOURCE_LOCAL,
            actor: $actor,
        );
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
                'address_line_1' => '1 QA Test Street',
                'town_city' => 'London',
                'postcode' => 'SW1A 1AA',
                'country' => 'GB',
                'mobile_number' => '+447400123456',
            ]
        );
    }

    private function setTimestamps(Model $model, CarbonImmutable $updatedAt): void
    {
        $model->forceFill([
            'created_at' => $updatedAt->subHour(),
            'updated_at' => $updatedAt,
        ])->saveQuietly();
    }
}
