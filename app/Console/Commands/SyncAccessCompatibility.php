<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncAccessCompatibility extends Command
{
    protected $signature = 'myapes:access-compatibility-sync';

    protected $description = 'Synchronize transitional access fields before an atomic release switch';

    public function handle(): int
    {
        if (! Schema::hasColumns('users', ['identity_type', User::accessLevelColumn()])) {
            $this->error('Access compatibility fields are missing.');

            return self::FAILURE;
        }

        if (! Schema::hasColumn('users', 'role')) {
            $this->line(
                'Legacy role column is absent; compatibility synchronization is not required.',
            );

            return self::SUCCESS;
        }

        if (DB::table('users')->whereNotIn('role', User::accessLevels())->exists()) {
            $this->error(
                'Access compatibility synchronization failed: users contain unsupported access levels.',
            );

            return self::FAILURE;
        }

        DB::transaction(static function (): void {
            DB::table('users')->update([
                'identity_type' => DB::raw(
                    "CASE WHEN oidc_sub IS NOT NULL AND TRIM(oidc_sub) <> '' ".
                    "THEN 'cloudron_oidc' ELSE 'local' END",
                ),
                User::accessLevelColumn() => DB::raw('role'),
            ]);
        });

        $this->line('Access compatibility fields synchronized.');

        return self::SUCCESS;
    }
}
