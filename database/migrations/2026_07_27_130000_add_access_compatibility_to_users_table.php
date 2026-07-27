<?php

use App\Support\AccessCompatibilityDatabaseGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $supportedAccessLevels = ['service_user', 'staff', 'admin', 'superadmin'];

        if (DB::table('users')
            ->where(static function ($query) use ($supportedAccessLevels): void {
                $query
                    ->whereNull('role')
                    ->orWhereRaw("TRIM(role) = ''")
                    ->orWhereNotIn('role', $supportedAccessLevels);
            })
            ->exists()) {
            throw new RuntimeException(
                'Cannot add access compatibility fields while users contain unsupported roles.',
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('identity_type', 32)->default('local')->index();
            $table->string('legacy_access_level', 32)->default('service_user')->index();
        });

        $guard = app(AccessCompatibilityDatabaseGuard::class);

        try {
            $guard->install();

            DB::table('users')->update([
                'identity_type' => DB::raw(
                    "CASE WHEN oidc_sub IS NOT NULL AND TRIM(oidc_sub) <> '' ".
                    "THEN 'cloudron_oidc' ELSE 'local' END",
                ),
                'legacy_access_level' => DB::raw('role'),
            ]);
        } catch (Throwable $exception) {
            try {
                $guard->drop();
            } catch (Throwable) {
                // Continue cleanup so the migration can be retried from the original schema.
            }

            $this->removeCompatibilityFields();

            throw $exception;
        }
    }

    public function down(): void
    {
        app(AccessCompatibilityDatabaseGuard::class)->drop();

        $this->removeCompatibilityFields();
    }

    private function removeCompatibilityFields(): void
    {
        $hasIdentityType = Schema::hasColumn('users', 'identity_type');
        $hasLegacyAccessLevel = Schema::hasColumn('users', 'legacy_access_level');

        if (! $hasIdentityType && ! $hasLegacyAccessLevel) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'identity_type')) {
                $table->dropIndex(['identity_type']);
                $table->dropColumn('identity_type');
            }

            if (Schema::hasColumn('users', 'legacy_access_level')) {
                $table->dropIndex(['legacy_access_level']);
                $table->dropColumn('legacy_access_level');
            }
        });
    }
};
