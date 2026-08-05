<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $rolePivotKey = $columnNames['role_pivot_key'] ?? 'role_id';
        $permissionPivotKey = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $modelMorphKey = $columnNames['model_morph_key'];
        $teamForeignKey = $columnNames['team_foreign_key'];

        throw_if(empty($tableNames), 'Permission table configuration is unavailable.');
        throw_if(
            ! config('permission.teams') || empty($teamForeignKey),
            'MyAPES authorization requires the Spatie teams schema.',
        );

        try {
            Schema::create($tableNames['permissions'], static function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->boolean('is_code_owned')->default(false)->index();
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });

            Schema::create(
                $tableNames['roles'],
                static function (Blueprint $table) use ($teamForeignKey): void {
                    $table->id();
                    $table->unsignedBigInteger($teamForeignKey)->nullable()->index();
                    $table->string('name');
                    $table->string('guard_name');
                    $table->boolean('is_protected')->default(false)->index();
                    $table->timestamps();
                    $table->unique(['name', 'guard_name']);
                },
            );

            Schema::create(
                $tableNames['model_has_permissions'],
                static function (Blueprint $table) use (
                    $tableNames,
                    $permissionPivotKey,
                    $modelMorphKey,
                    $teamForeignKey,
                ): void {
                    $table->unsignedBigInteger($permissionPivotKey);
                    $table->unsignedBigInteger($teamForeignKey)
                        ->nullable()
                        ->index();
                    $table->string('model_type');
                    $table->unsignedBigInteger($modelMorphKey);
                    $table->index(
                        [$modelMorphKey, 'model_type'],
                        'model_has_permissions_model_id_model_type_index',
                    );
                    $table->foreign($permissionPivotKey)
                        ->references('id')
                        ->on($tableNames['permissions'])
                        ->cascadeOnDelete();
                    $table->primary(
                        [$permissionPivotKey, $modelMorphKey, 'model_type'],
                        'model_has_permissions_permission_model_type_primary',
                    );
                },
            );

            Schema::create(
                $tableNames['model_has_roles'],
                static function (Blueprint $table) use (
                    $tableNames,
                    $rolePivotKey,
                    $modelMorphKey,
                    $teamForeignKey,
                ): void {
                    $table->unsignedBigInteger($rolePivotKey);
                    $table->unsignedBigInteger($teamForeignKey)
                        ->nullable()
                        ->index();
                    $table->string('model_type');
                    $table->unsignedBigInteger($modelMorphKey);
                    $table->index(
                        [$modelMorphKey, 'model_type'],
                        'model_has_roles_model_id_model_type_index',
                    );
                    $table->foreign($rolePivotKey)
                        ->references('id')
                        ->on($tableNames['roles'])
                        ->cascadeOnDelete();
                    $table->primary(
                        [$rolePivotKey, $modelMorphKey, 'model_type'],
                        'model_has_roles_role_model_type_primary',
                    );
                },
            );

            Schema::create(
                $tableNames['role_has_permissions'],
                static function (Blueprint $table) use (
                    $tableNames,
                    $rolePivotKey,
                    $permissionPivotKey,
                ): void {
                    $table->unsignedBigInteger($permissionPivotKey);
                    $table->unsignedBigInteger($rolePivotKey);
                    $table->foreign($permissionPivotKey)
                        ->references('id')
                        ->on($tableNames['permissions'])
                        ->cascadeOnDelete();
                    $table->foreign($rolePivotKey)
                        ->references('id')
                        ->on($tableNames['roles'])
                        ->cascadeOnDelete();
                    $table->primary(
                        [$permissionPivotKey, $rolePivotKey],
                        'role_has_permissions_permission_id_role_id_primary',
                    );
                },
            );

            app('cache')
                ->store(config('permission.cache.store') !== 'default'
                    ? config('permission.cache.store')
                    : null)
                ->forget(config('permission.cache.key'));
        } catch (Throwable $exception) {
            $this->dropTables($tableNames);

            throw $exception;
        }
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');

        $this->dropTables($tableNames);
    }

    /**
     * @param  array<string, string>  $tableNames
     */
    private function dropTables(array $tableNames): void
    {
        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['roles']);
        Schema::dropIfExists($tableNames['permissions']);
    }
};
