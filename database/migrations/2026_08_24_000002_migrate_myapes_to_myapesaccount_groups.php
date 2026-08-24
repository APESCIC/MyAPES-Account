<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
            DB::table('directory_groups')
                ->where('name', $from)
                ->update(['name' => $to]);
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
        $reverse = array_flip($this->renameMap);

        foreach ($reverse as $from => $to) {
            DB::table('directory_groups')
                ->where('name', $from)
                ->update(['name' => $to]);
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
};
