<?php

use App\Services\AuthorizationMetadataSynchronizer;
use App\Services\AuthorizationProfile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('module_installations')) {
            Schema::create('module_installations', function (Blueprint $table): void {
                $table->id();
                $table->string('sub_core_key', 64);
                $table->string('module_key', 64);
                $table->boolean('enabled')->default(false)->index();
                $table->unsignedBigInteger('lock_version')->default(1);
                $table->timestamp('installed_at', precision: 6);
                $table->foreignId('installed_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamp('enabled_at', precision: 6)->nullable();
                $table->foreignId('enabled_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamp('disabled_at', precision: 6)->nullable();
                $table->foreignId('disabled_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamps(precision: 6);
                $table->unique(['sub_core_key', 'module_key']);
                $table->index(['sub_core_key', 'enabled']);
            });
        }

        $now = now();
        foreach ([
            ['apes-cic', 'tickets'],
            ['pet-care-clinic', 'consultations'],
            ['pet-care-clinic', 'pet-profiles'],
            ['shelter-rescue', 'cases'],
            ['shelter-rescue', 'pet-profiles'],
        ] as [$subCoreKey, $moduleKey]) {
            DB::table('module_installations')->insertOrIgnore([
                'sub_core_key' => $subCoreKey,
                'module_key' => $moduleKey,
                'enabled' => true,
                'lock_version' => 1,
                'installed_at' => $now,
                'installed_by' => null,
                'enabled_at' => $now,
                'enabled_by' => null,
                'disabled_at' => null,
                'disabled_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        app(AuthorizationProfile::class)->flushRuntimeCache();
        app(AuthorizationMetadataSynchronizer::class)->synchronize();
    }

    public function down(): void
    {
        Schema::dropIfExists('module_installations');

        $prefixes = [
            'apes-cic.%',
            'shelter-rescue.%',
            'pet-care-clinic.%',
        ];
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where(function ($query) use ($prefixes): void {
                foreach ($prefixes as $prefix) {
                    $query->orWhere('name', 'like', $prefix);
                }
            })
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            foreach ([
                'role_has_permissions',
                'model_has_permissions',
                'permission_sources',
            ] as $pivotTable) {
                if (Schema::hasTable($pivotTable)) {
                    DB::table($pivotTable)
                        ->whereIn('permission_id', $permissionIds)
                        ->delete();
                }
            }

            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }

        if (! Schema::hasTable('roles')
            || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $administratorId = DB::table('roles')
            ->where('guard_name', 'web')
            ->where('name', 'administrator')
            ->value('id');
        $managePermissionId = DB::table('permissions')
            ->where('guard_name', 'web')
            ->where('name', 'admin.modules.manage')
            ->value('id');

        if (is_numeric($administratorId) && is_numeric($managePermissionId)) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'role_id' => $administratorId,
                'permission_id' => $managePermissionId,
            ]);
        }
    }
};
