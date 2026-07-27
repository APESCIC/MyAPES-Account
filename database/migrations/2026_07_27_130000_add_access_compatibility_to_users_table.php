<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $supportedAccessLevels = ['service_user', 'staff', 'admin', 'superadmin'];

        if (DB::table('users')->whereNotIn('role', $supportedAccessLevels)->exists()) {
            throw new RuntimeException(
                'Cannot add access compatibility fields while users contain unsupported roles.',
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('identity_type', 32)->default('local')->index();
            $table->string('legacy_access_level', 32)->default('service_user')->index();
        });

        DB::table('users')->update([
            'identity_type' => DB::raw(
                "CASE WHEN oidc_sub IS NOT NULL AND TRIM(oidc_sub) <> '' ".
                "THEN 'cloudron_oidc' ELSE 'local' END",
            ),
            'legacy_access_level' => DB::raw('role'),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['identity_type']);
            $table->dropIndex(['legacy_access_level']);
            $table->dropColumn(['identity_type', 'legacy_access_level']);
        });
    }
};
