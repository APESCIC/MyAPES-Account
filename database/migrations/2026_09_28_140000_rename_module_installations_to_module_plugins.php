<?php

use App\Services\AuthorizationMetadataSynchronizer;
use App\Services\AuthorizationProfile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Forward-only 1:1 copy of plugin enablement storage (#288).
 *
 * Copies every module_installations / module_settings row into module_plugins /
 * module_plugin_settings, then drops the legacy tables. Column names stay
 * sub_core_key / module_key so existing queries and indexes remain stable;
 * PluginEnablement exposes module / plugin aliases in code.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('module_plugins')) {
            Schema::create('module_plugins', function (Blueprint $table): void {
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

        if (Schema::hasTable('module_installations')) {
            $rows = DB::table('module_installations')->orderBy('id')->get();

            foreach ($rows as $row) {
                DB::table('module_plugins')->insertOrIgnore([
                    'id' => $row->id,
                    'sub_core_key' => $row->sub_core_key,
                    'module_key' => $row->module_key,
                    'enabled' => (bool) $row->enabled,
                    'lock_version' => $row->lock_version,
                    'installed_at' => $row->installed_at,
                    'installed_by' => $row->installed_by,
                    'enabled_at' => $row->enabled_at,
                    'enabled_by' => $row->enabled_by,
                    'disabled_at' => $row->disabled_at,
                    'disabled_by' => $row->disabled_by,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }

            Schema::drop('module_installations');
        }

        if (! Schema::hasTable('module_plugin_settings')) {
            Schema::create('module_plugin_settings', function (Blueprint $table): void {
                $table->id();
                $table->string('sub_core_key', 64);
                $table->string('module_key', 64);
                $table->json('settings');
                $table->unsignedInteger('lock_version')->default(1);
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['sub_core_key', 'module_key']);
            });
        }

        if (Schema::hasTable('module_settings')) {
            $rows = DB::table('module_settings')->orderBy('id')->get();

            foreach ($rows as $row) {
                DB::table('module_plugin_settings')->insertOrIgnore([
                    'id' => $row->id,
                    'sub_core_key' => $row->sub_core_key,
                    'module_key' => $row->module_key,
                    'settings' => $row->settings,
                    'lock_version' => $row->lock_version,
                    'updated_by' => $row->updated_by,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }

            Schema::drop('module_settings');
        }

        app(AuthorizationProfile::class)->flushRuntimeCache();
        app(AuthorizationMetadataSynchronizer::class)->synchronize();
    }

    public function down(): void
    {
        if (! Schema::hasTable('module_installations') && Schema::hasTable('module_plugins')) {
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

            foreach (DB::table('module_plugins')->orderBy('id')->get() as $row) {
                DB::table('module_installations')->insert([
                    'id' => $row->id,
                    'sub_core_key' => $row->sub_core_key,
                    'module_key' => $row->module_key,
                    'enabled' => (bool) $row->enabled,
                    'lock_version' => $row->lock_version,
                    'installed_at' => $row->installed_at,
                    'installed_by' => $row->installed_by,
                    'enabled_at' => $row->enabled_at,
                    'enabled_by' => $row->enabled_by,
                    'disabled_at' => $row->disabled_at,
                    'disabled_by' => $row->disabled_by,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        }

        Schema::dropIfExists('module_plugins');

        if (! Schema::hasTable('module_settings') && Schema::hasTable('module_plugin_settings')) {
            Schema::create('module_settings', function (Blueprint $table): void {
                $table->id();
                $table->string('sub_core_key', 64);
                $table->string('module_key', 64);
                $table->json('settings');
                $table->unsignedInteger('lock_version')->default(1);
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['sub_core_key', 'module_key']);
            });

            foreach (DB::table('module_plugin_settings')->orderBy('id')->get() as $row) {
                DB::table('module_settings')->insert([
                    'id' => $row->id,
                    'sub_core_key' => $row->sub_core_key,
                    'module_key' => $row->module_key,
                    'settings' => $row->settings,
                    'lock_version' => $row->lock_version,
                    'updated_by' => $row->updated_by,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }
        }

        Schema::dropIfExists('module_plugin_settings');
    }
};
