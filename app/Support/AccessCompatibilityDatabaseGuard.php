<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class AccessCompatibilityDatabaseGuard
{
    private const INSERT_TRIGGER = 'users_access_compatibility_insert';

    private const UPDATE_TRIGGER = 'users_access_compatibility_update';

    public function install(): void
    {
        if ($this->isInstalled()) {
            return;
        }

        $this->drop();

        try {
            match (DB::connection()->getDriverName()) {
                'sqlite' => $this->installSqlite(),
                'mysql', 'mariadb' => $this->installMysql(),
                default => throw new RuntimeException(
                    'Access compatibility database guard is unsupported.',
                ),
            };
        } catch (\Throwable $exception) {
            $this->drop();

            throw $exception;
        }
    }

    public function drop(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS '.self::INSERT_TRIGGER);
        DB::unprepared('DROP TRIGGER IF EXISTS '.self::UPDATE_TRIGGER);
    }

    public function isInstalled(): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $row = DB::selectOne(
                "SELECT COUNT(*) AS aggregate
                 FROM sqlite_master
                 WHERE type = 'trigger' AND name IN (?, ?)",
                [self::INSERT_TRIGGER, self::UPDATE_TRIGGER],
            );

            return (int) ($row->aggregate ?? 0) === 2;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $row = DB::selectOne(
                'SELECT COUNT(*) AS aggregate
                 FROM information_schema.triggers
                 WHERE trigger_schema = DATABASE()
                   AND event_object_table = ?
                   AND trigger_name IN (?, ?)',
                ['users', self::INSERT_TRIGGER, self::UPDATE_TRIGGER],
            );

            return (int) ($row->aggregate ?? 0) === 2;
        }

        return false;
    }

    private function installSqlite(): void
    {
        DB::unprepared(
            'CREATE TRIGGER IF NOT EXISTS '.self::INSERT_TRIGGER.'
             AFTER INSERT ON users
             BEGIN
                 SELECT RAISE(ABORT, \'Unsupported legacy access level.\')
                 WHERE NEW.role IS NULL
                    OR TRIM(NEW.role) = \'\'
                    OR NEW.role NOT IN (\'service_user\', \'staff\', \'admin\', \'superadmin\');
                 UPDATE users
                 SET identity_type = CASE
                         WHEN NEW.oidc_sub IS NOT NULL AND TRIM(NEW.oidc_sub) <> \'\'
                             THEN \'cloudron_oidc\'
                         ELSE \'local\'
                     END,
                     legacy_access_level = NEW.role
                 WHERE id = NEW.id;
             END',
        );

        DB::unprepared(
            'CREATE TRIGGER IF NOT EXISTS '.self::UPDATE_TRIGGER.'
             AFTER UPDATE OF role, oidc_sub ON users
             BEGIN
                 SELECT RAISE(ABORT, \'Unsupported legacy access level.\')
                 WHERE NEW.role IS NULL
                    OR TRIM(NEW.role) = \'\'
                    OR NEW.role NOT IN (\'service_user\', \'staff\', \'admin\', \'superadmin\');
                 UPDATE users
                 SET identity_type = CASE
                         WHEN NEW.oidc_sub IS NOT NULL AND TRIM(NEW.oidc_sub) <> \'\'
                             THEN \'cloudron_oidc\'
                         ELSE \'local\'
                     END,
                     legacy_access_level = NEW.role
                 WHERE id = NEW.id;
             END',
        );
    }

    private function installMysql(): void
    {
        DB::unprepared(
            'CREATE TRIGGER '.self::INSERT_TRIGGER.'
             BEFORE INSERT ON users
             FOR EACH ROW
             BEGIN
                 IF NEW.role IS NULL
                    OR TRIM(NEW.role) = \'\'
                    OR NEW.role NOT IN (\'service_user\', \'staff\', \'admin\', \'superadmin\')
                 THEN
                     SIGNAL SQLSTATE \'45000\'
                         SET MESSAGE_TEXT = \'Unsupported legacy access level.\';
                 END IF;
                 SET NEW.identity_type = CASE
                     WHEN NEW.oidc_sub IS NOT NULL AND TRIM(NEW.oidc_sub) <> \'\'
                         THEN \'cloudron_oidc\'
                     ELSE \'local\'
                 END;
                 SET NEW.legacy_access_level = NEW.role;
             END',
        );

        DB::unprepared(
            'CREATE TRIGGER '.self::UPDATE_TRIGGER.'
             BEFORE UPDATE ON users
             FOR EACH ROW
             BEGIN
                 IF NEW.role IS NULL
                    OR TRIM(NEW.role) = \'\'
                    OR NEW.role NOT IN (\'service_user\', \'staff\', \'admin\', \'superadmin\')
                 THEN
                     SIGNAL SQLSTATE \'45000\'
                         SET MESSAGE_TEXT = \'Unsupported legacy access level.\';
                 END IF;
                 SET NEW.identity_type = CASE
                     WHEN NEW.oidc_sub IS NOT NULL AND TRIM(NEW.oidc_sub) <> \'\'
                         THEN \'cloudron_oidc\'
                     ELSE \'local\'
                 END;
                 SET NEW.legacy_access_level = NEW.role;
             END',
        );
    }
}
