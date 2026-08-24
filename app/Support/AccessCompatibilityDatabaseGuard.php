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
                'mysql' => $this->installMysql(),
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
            $rows = DB::select(
                "SELECT name, sql
                 FROM sqlite_master
                 WHERE type = 'trigger' AND name IN (?, ?)",
                [self::INSERT_TRIGGER, self::UPDATE_TRIGGER],
            );

            $definitions = collect($rows)->pluck('sql', 'name');

            return $this->definitionsMatch([
                self::INSERT_TRIGGER => $this->sqliteInsertSql(),
                self::UPDATE_TRIGGER => $this->sqliteUpdateSql(),
            ], $definitions->all());
        }

        if ($driver === 'mysql') {
            $rows = DB::select(
                'SELECT TRIGGER_NAME AS trigger_name,
                        ACTION_TIMING AS action_timing,
                        EVENT_MANIPULATION AS event_manipulation,
                        ACTION_STATEMENT AS action_statement
                 FROM information_schema.triggers
                 WHERE trigger_schema = DATABASE()
                   AND event_object_table = ?
                   AND trigger_name IN (?, ?)',
                ['users', self::INSERT_TRIGGER, self::UPDATE_TRIGGER],
            );

            $definitions = collect($rows)->keyBy('trigger_name');
            $insert = $definitions->get(self::INSERT_TRIGGER);
            $update = $definitions->get(self::UPDATE_TRIGGER);

            return $insert !== null
                && $update !== null
                && strtoupper((string) $insert->action_timing) === 'BEFORE'
                && strtoupper((string) $insert->event_manipulation) === 'INSERT'
                && strtoupper((string) $update->action_timing) === 'BEFORE'
                && strtoupper((string) $update->event_manipulation) === 'UPDATE'
                && $this->normalizeSql((string) $insert->action_statement)
                    === $this->normalizeSql($this->mysqlInsertBody())
                && $this->normalizeSql((string) $update->action_statement)
                    === $this->normalizeSql($this->mysqlUpdateBody());
        }

        return false;
    }

    private function installSqlite(): void
    {
        DB::unprepared($this->sqliteInsertSql());
        DB::unprepared($this->sqliteUpdateSql());
    }

    private function sqliteInsertSql(): string
    {
        return 'CREATE TRIGGER IF NOT EXISTS '.self::INSERT_TRIGGER.'
             AFTER INSERT ON users
             BEGIN
                 SELECT RAISE(ABORT, \'Unsupported legacy access level.\')
                 WHERE NEW.role IS NULL
                    OR TRIM(NEW.role) = \'\'
                    OR NEW.role NOT IN (\'service_user\', \'student\', \'volunteer\', \'staff\', \'admin\', \'superadmin\');
                 UPDATE users
                 SET identity_type = CASE
                         WHEN NEW.oidc_sub IS NOT NULL AND TRIM(NEW.oidc_sub) <> \'\'
                             THEN \'cloudron_oidc\'
                         ELSE \'local\'
                     END,
                     legacy_access_level = NEW.role
                 WHERE id = NEW.id;
             END';
    }

    private function sqliteUpdateSql(): string
    {
        return 'CREATE TRIGGER IF NOT EXISTS '.self::UPDATE_TRIGGER.'
             AFTER UPDATE OF role, oidc_sub ON users
             BEGIN
                 SELECT RAISE(ABORT, \'Unsupported legacy access level.\')
                 WHERE NEW.role IS NULL
                    OR TRIM(NEW.role) = \'\'
                    OR NEW.role NOT IN (\'service_user\', \'student\', \'volunteer\', \'staff\', \'admin\', \'superadmin\');
                 UPDATE users
                 SET identity_type = CASE
                         WHEN NEW.oidc_sub IS NOT NULL AND TRIM(NEW.oidc_sub) <> \'\'
                             THEN \'cloudron_oidc\'
                         ELSE \'local\'
                     END,
                     legacy_access_level = NEW.role
                 WHERE id = NEW.id;
             END';
    }

    private function installMysql(): void
    {
        DB::unprepared(
            'CREATE TRIGGER '.self::INSERT_TRIGGER.'
             BEFORE INSERT ON users
             FOR EACH ROW
             '.$this->mysqlInsertBody(),
        );

        DB::unprepared(
            'CREATE TRIGGER '.self::UPDATE_TRIGGER.'
             BEFORE UPDATE ON users
             FOR EACH ROW
             '.$this->mysqlUpdateBody(),
        );
    }

    private function mysqlInsertBody(): string
    {
        return 'BEGIN
                 IF NEW.role IS NULL
                    OR TRIM(NEW.role) = \'\'
                    OR NEW.role NOT IN (\'service_user\', \'student\', \'volunteer\', \'staff\', \'admin\', \'superadmin\')
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
             END';
    }

    private function mysqlUpdateBody(): string
    {
        return 'BEGIN
                 IF NEW.role IS NULL
                    OR TRIM(NEW.role) = \'\'
                    OR NEW.role NOT IN (\'service_user\', \'student\', \'volunteer\', \'staff\', \'admin\', \'superadmin\')
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
             END';
    }

    /**
     * @param  array<string, string>  $expected
     * @param  array<string, mixed>  $actual
     */
    private function definitionsMatch(
        array $expected,
        array $actual,
    ): bool {
        if (array_keys($expected) !== array_keys($actual)) {
            ksort($expected);
            ksort($actual);

            if (array_keys($expected) !== array_keys($actual)) {
                return false;
            }
        }

        foreach ($expected as $name => $definition) {
            if (! is_string($actual[$name] ?? null)
                || $this->normalizeSql($actual[$name])
                    !== $this->normalizeSql($definition)) {
                return false;
            }
        }

        return true;
    }

    private function normalizeSql(string $sql): string
    {
        $normalized = strtolower(str_replace('`', '', $sql));
        $normalized = str_replace('if not exists', '', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim((string) $normalized, " \t\n\r\0\x0B;");
    }
}
