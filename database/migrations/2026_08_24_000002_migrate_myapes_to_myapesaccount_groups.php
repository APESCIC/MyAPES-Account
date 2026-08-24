<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $renameMap = [
        'myapes.staff' => 'myapesaccount.staff',
        'myapes.admin' => 'myapesaccount.admin',
        'myapes.superadmin' => 'myapesaccount.superadmin',
        'myapes.superadmins' => 'myapesaccount.superadmin',
        'myapes.students' => 'myapesaccount.student',
        'myapes.volunteers' => 'myapesaccount.volunteer',
        'myapes.vounteers' => 'myapesaccount.volunteer',
    ];

    /**
     * @var array<int, string>
     */
    private array $deleteGroups = [
        'myapes.admins',
    ];

    public function up(): void
    {
        foreach ($this->renameMap as $from => $to) {
            $this->renameOrMergeDirectoryGroup($from, $to);
        }

        if ($this->deleteGroups !== []) {
            DB::table('directory_groups')
                ->whereIn('name', $this->deleteGroups)
                ->delete();
        }

        $users = DB::table('users')
            ->whereNotNull('ldap_groups')
            ->get(['id', 'ldap_groups']);

        foreach ($users as $user) {
            $groups = json_decode((string) $user->ldap_groups, true);

            if (! is_array($groups)) {
                continue;
            }

            $updated = [];

            foreach ($groups as $group) {
                if (! is_string($group)) {
                    continue;
                }

                $normalized = strtolower(trim($group));
                $updated[] = $this->renameMap[$normalized] ?? $normalized;
            }

            $updated = array_values(array_unique($updated));
            sort($updated);

            DB::table('users')
                ->where('id', $user->id)
                ->update(['ldap_groups' => json_encode($updated)]);
        }
    }

    public function down(): void
    {
        $reverse = [];

        foreach ($this->renameMap as $from => $to) {
            if (! array_key_exists($to, $reverse)) {
                $reverse[$to] = $from;
            }
        }

        foreach ($reverse as $from => $to) {
            $this->renameOrMergeDirectoryGroup($from, $to);
        }

        $users = DB::table('users')
            ->whereNotNull('ldap_groups')
            ->get(['id', 'ldap_groups']);

        foreach ($users as $user) {
            $groups = json_decode((string) $user->ldap_groups, true);

            if (! is_array($groups)) {
                continue;
            }

            $updated = [];

            foreach ($groups as $group) {
                if (! is_string($group)) {
                    continue;
                }

                $normalized = strtolower(trim($group));
                $updated[] = $reverse[$normalized] ?? $normalized;
            }

            $updated = array_values(array_unique($updated));
            sort($updated);

            DB::table('users')
                ->where('id', $user->id)
                ->update(['ldap_groups' => json_encode($updated)]);
        }
    }

    private function renameOrMergeDirectoryGroup(string $from, string $to): void
    {
        $legacy = DB::table('directory_groups')
            ->where('name', $from)
            ->first();

        if ($legacy === null) {
            return;
        }

        $canonical = DB::table('directory_groups')
            ->where('name', $to)
            ->first();

        if ($canonical === null) {
            DB::table('directory_groups')
                ->where('id', $legacy->id)
                ->update(['name' => $to]);

            return;
        }

        if ((int) $legacy->id === (int) $canonical->id) {
            return;
        }

        $this->reassignDirectoryGroupReferences(
            (int) $legacy->id,
            (int) $canonical->id,
        );

        DB::table('directory_groups')
            ->where('id', $legacy->id)
            ->delete();
    }

    private function reassignDirectoryGroupReferences(int $fromId, int $toId): void
    {
        if (Schema::hasTable('directory_group_role_mappings')) {
            $mappings = DB::table('directory_group_role_mappings')
                ->where('directory_group_id', $fromId)
                ->get(['id', 'role_id']);

            foreach ($mappings as $mapping) {
                $exists = DB::table('directory_group_role_mappings')
                    ->where('directory_group_id', $toId)
                    ->where('role_id', $mapping->role_id)
                    ->exists();

                if ($exists) {
                    DB::table('directory_group_role_mappings')
                        ->where('id', $mapping->id)
                        ->delete();

                    continue;
                }

                DB::table('directory_group_role_mappings')
                    ->where('id', $mapping->id)
                    ->update(['directory_group_id' => $toId]);
            }
        }

        if (Schema::hasTable('role_sources')) {
            $sources = DB::table('role_sources')
                ->where('directory_group_id', $fromId)
                ->get(['id', 'user_id', 'role_id', 'source_key']);

            foreach ($sources as $source) {
                $nextSourceKey = preg_replace(
                    '/^directory:'.preg_quote((string) $fromId, '/').'\z/',
                    'directory:'.$toId,
                    (string) $source->source_key,
                    1,
                );

                if (! is_string($nextSourceKey) || $nextSourceKey === '') {
                    $nextSourceKey = 'directory:'.$toId;
                }

                $exists = DB::table('role_sources')
                    ->where('user_id', $source->user_id)
                    ->where('role_id', $source->role_id)
                    ->where('source_key', $nextSourceKey)
                    ->exists();

                if ($exists) {
                    DB::table('role_sources')
                        ->where('id', $source->id)
                        ->delete();

                    continue;
                }

                DB::table('role_sources')
                    ->where('id', $source->id)
                    ->update([
                        'directory_group_id' => $toId,
                        'source_key' => $nextSourceKey,
                    ]);
            }
        }
    }
};
