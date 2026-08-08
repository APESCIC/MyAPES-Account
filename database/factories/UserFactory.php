<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\RoleSource;
use App\Models\User;
use App\Services\AuthorizationAccountSynchronizer;
use App\Services\AuthorizationProfile;
use App\Services\AuthorizationRoleMaterializer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    public function configure(): static
    {
        return $this
            ->afterMaking(static function (User $user): void {
                if (! is_string($user->getAttribute(User::accessLevelColumn()))
                    || trim((string) $user->getAttribute(User::accessLevelColumn())) === '') {
                    $user->setAccessLevel(User::ROLE_SERVICE_USER);
                }
            })
            ->afterCreating(static function (User $user): void {
                $schema = $user->getConnection()->getSchemaBuilder();

                if (! $schema->hasTable('roles')
                    || ! $schema->hasTable('role_sources')
                    || ! $schema->hasTable('model_has_roles')
                    || $user->accessLevel() !== User::ROLE_SERVICE_USER) {
                    return;
                }

                app(AuthorizationAccountSynchronizer::class)
                    ->grantPublicBaseline($user);

                if (in_array($user->identity_type, [User::IDENTITY_LOCAL, User::IDENTITY_HYBRID], true)
                    && $schema->hasTable('user_service_selections')) {
                    $user->contactPreference()->firstOrCreate([]);
                    foreach (['apes-cic', 'shelter-rescue', 'pet-care-clinic'] as $service) {
                        $user->serviceSelections()->firstOrCreate(['sub_core_key' => $service]);
                    }
                }
            });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'onboarding_completed_at' => now(),
            'identity_type' => User::IDENTITY_LOCAL,
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function accessLevel(string $accessLevel): static
    {
        return $this->afterMaking(
            static fn (User $user): User => $user->setAccessLevel($accessLevel),
        );
    }

    public function phaseAAccessLevel(string $accessLevel): static
    {
        return $this->accessLevel($accessLevel);
    }

    public function protectedRole(
        string $roleName,
        string $source = RoleSource::SOURCE_SYSTEM,
    ): static {
        if ($source === RoleSource::SOURCE_DIRECTORY) {
            throw new InvalidArgumentException(
                'Directory protected-role fixtures must use the directory synchronization boundary.',
            );
        }

        return $this
            ->afterMaking(static function (User $user) use ($roleName): void {
                $legacy = app(AuthorizationProfile::class)
                    ->legacyAccessLevelFor($roleName);
                $user->setAccessLevel($legacy);
            })
            ->afterCreating(static function (User $user) use (
                $roleName,
                $source,
            ): void {
                $role = Role::query()
                    ->where('guard_name', 'web')
                    ->where('name', $roleName)
                    ->firstOrFail();
                app(AuthorizationRoleMaterializer::class)
                    ->grant($user, $role, $source);
            });
    }

    public function customRole(string $roleName): static
    {
        return $this->afterCreating(
            static function (User $user) use ($roleName): void {
                $role = Role::query()->firstOrCreate([
                    'name' => $roleName,
                    'guard_name' => 'web',
                ]);

                if ($role->is_protected) {
                    throw new InvalidArgumentException(
                        'Custom-role fixtures cannot use protected role names.',
                    );
                }

                app(AuthorizationRoleMaterializer::class)->grant(
                    $user,
                    $role,
                    RoleSource::SOURCE_LOCAL,
                    actor: $user,
                );
            },
        );
    }

    public function directoryIdentity(string $subject): static
    {
        return $this->state(fn (array $attributes) => [
            'identity_type' => User::IDENTITY_CLOUDRON_OIDC,
            'oidc_sub' => $subject,
            'ldap_groups' => [],
        ]);
    }

    public function authorizationContextEpoch(int $epoch): static
    {
        if ($epoch < 1) {
            throw new InvalidArgumentException(
                'Authorization context epochs must be positive.',
            );
        }

        return $this->state(fn (array $attributes) => [
            'authorization_epoch' => $epoch,
        ]);
    }

    public function cloudronIdentity(string $subject): static
    {
        return $this->directoryIdentity($subject);
    }
}
