<?php

use App\Services\AuthorizationMetadataSynchronizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * @var array<int, string>
     */
    private array $roles = [
        'student',
        'volunteer',
    ];

    /**
     * @var array<int, string>
     */
    private array $permissions = [
        'student.access',
        'volunteer.access',
    ];

    public function up(): void
    {
        $now = now();

        foreach ($this->roles as $role) {
            DB::table('roles')->insertOrIgnore([
                'name' => $role,
                'guard_name' => 'web',
                'is_protected' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($this->permissions as $permission) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permission,
                'guard_name' => 'web',
                'is_code_owned' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (class_exists(AuthorizationMetadataSynchronizer::class)) {
            app(AuthorizationMetadataSynchronizer::class)->synchronize();
        }

        if (class_exists(\App\Support\AuthorizationCompatibilityDatabaseGuard::class)) {
            app(\App\Support\AuthorizationCompatibilityDatabaseGuard::class)->install();
        }
    }

    public function down(): void
    {
        // Authorization metadata downgrades are handled manually.
    }
};
