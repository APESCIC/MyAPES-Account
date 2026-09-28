<?php

use App\Core\Eloquent\MorphMap;
use App\Support\AuthorizationCompatibilityDatabaseGuard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rewrite stored polymorphic FQCNs to stable morph aliases (#281).
 *
 * Runs after the Core morph map is enforced so audit, attachments, notifications,
 * and Spatie pivots keep resolving when models later change namespace.
 */
return new class extends Migration
{
    /**
     * @var list<array{0: string, 1: string}>
     */
    private array $columns = [
        ['audit_logs', 'auditable_type'],
        ['support_attachments', 'attachable_type'],
        ['notifications', 'notifiable_type'],
        ['model_has_roles', 'model_type'],
        ['model_has_permissions', 'model_type'],
    ];

    public function up(): void
    {
        $fqcnToAlias = [];

        foreach (MorphMap::aliases() as $alias => $class) {
            $fqcnToAlias[$class] = $alias;
        }

        // Auth pivot triggers reject model_type UPDATEs on provenanced rows.
        // Drop them for the rewrite window, then reinstall with the alias CHAR.
        $this->rewriteWithAuthTriggersSuspended($fqcnToAlias);
    }

    public function down(): void
    {
        $this->rewriteWithAuthTriggersSuspended(MorphMap::aliases());
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function rewriteWithAuthTriggersSuspended(array $replacements): void
    {
        $guard = null;

        if (class_exists(AuthorizationCompatibilityDatabaseGuard::class)
            && Schema::hasTable('model_has_roles')) {
            $guard = app(AuthorizationCompatibilityDatabaseGuard::class);
            $guard->drop();
        }

        try {
            foreach ($this->columns as [$table, $column]) {
                $this->rewriteColumn($table, $column, $replacements);
            }
        } finally {
            // Rebuild triggers so they match the morph alias ("user"), not the old FQCN.
            $guard?->upgrade();
        }
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function rewriteColumn(string $table, string $column, array $replacements): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        foreach ($replacements as $from => $to) {
            DB::table($table)
                ->where($column, $from)
                ->update([$column => $to]);
        }
    }
};
