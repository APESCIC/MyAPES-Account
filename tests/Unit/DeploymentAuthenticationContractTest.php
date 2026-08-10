<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class DeploymentAuthenticationContractTest extends TestCase
{
    public function test_phase_b_activation_runs_once_in_the_fail_closed_order_as_www_data(): void
    {
        $script = $this->read('scripts/deploy/activate-release.sh');
        $activation = substr(
            $script,
            $this->position($script, 'run_artisan optimize:clear'),
        );

        $orderedCommands = [
            'optimize:clear',
            'myapes:authorization-preflight --no-interaction --no-ansi',
            'myapes:modules:preflight --no-interaction --no-ansi',
            'myapes:accounts:preflight --no-interaction --no-ansi',
            'migrate --force',
            'myapes:modules:sync --no-interaction --no-ansi',
            'config:cache',
            'route:cache',
            'view:cache',
            'permission:cache-reset --no-interaction',
            'myapes:directory-sync --source=manual --no-interaction --no-ansi',
            'myapes:authorization-sync --no-interaction --no-ansi',
            'permission:cache-reset --no-interaction',
            'myapes:modules:check --no-interaction --no-ansi',
            'myapes:accounts:check --no-interaction --no-ansi',
            'myapes:authorization-check --no-interaction --no-ansi',
            'mv -Tf "${CURRENT_LINK}.next" "$CURRENT_LINK"',
        ];
        $offset = 0;

        foreach ($orderedCommands as $command) {
            $position = strpos($activation, $command, $offset);
            $this->assertNotFalse(
                $position,
                "Expected activation to contain [{$command}] after offset {$offset}.",
            );
            $offset = $position + strlen($command);
        }

        foreach (array_slice($orderedCommands, 0, -1) as $command) {
            $line = $this->lineContaining($activation, $command);
            $this->assertStringContainsString(
                'run_artisan ',
                $line,
                "Expected [{$command}] to run through the production Artisan helper.",
            );
        }

        $this->assertStringContainsString(
            "run_artisan() {\n  sudo -E -u www-data env APP_ENV=production \\\n    \"\$PHP_BIN\" \"\${RELEASE_DIR}/artisan\" \"\$@\"\n}",
            $script,
        );
        $this->assertStringContainsString(
            "if ! run_artisan env --no-ansi | grep -Eq 'production'; then",
            $script,
        );
        $this->assertSame(2, substr_count(
            $script,
            'run_artisan permission:cache-reset',
        ));
        $this->assertSame(1, substr_count($script, 'run_artisan myapes:authorization-preflight'));
        $this->assertSame(1, substr_count($script, 'run_artisan myapes:modules:preflight'));
        $this->assertSame(1, substr_count($script, 'run_artisan myapes:accounts:preflight'));
        $this->assertSame(1, substr_count($script, 'run_artisan myapes:modules:sync'));
        $this->assertSame(1, substr_count($script, 'run_artisan myapes:modules:check'));
        $this->assertSame(1, substr_count($script, 'run_artisan myapes:accounts:check'));
        $this->assertSame(1, substr_count($script, 'run_artisan myapes:directory-sync'));
        $this->assertSame(1, substr_count($script, 'run_artisan myapes:authorization-sync'));
        $this->assertSame(1, substr_count($script, 'run_artisan myapes:authorization-check'));
        $this->assertStringNotContainsString('myapes:auth-check', $script);
        $this->assertStringNotContainsString('myapes:access-compatibility-sync', $script);
    }

    public function test_pre_switch_activation_failure_restores_the_current_authorization_matrix_before_reopening(): void
    {
        $result = $this->runActivationRecoveryHarness('pre-switch-failure');

        $this->assertFalse($result['process']->isSuccessful());
        $this->assertSame([
            '/usr/bin/php8.4 /release/new/artisan myapes:modules:rollback-check --target-release=/release/current --no-interaction --no-ansi',
            '/usr/bin/php8.4 /release/current/artisan permission:cache-reset --no-interaction --no-ansi',
            '/usr/bin/php8.4 /release/current/artisan myapes:authorization-sync --no-interaction --no-ansi',
            '/usr/bin/php8.4 /release/current/artisan permission:cache-reset --no-interaction --no-ansi',
            '/usr/bin/php8.4 /release/current/artisan myapes:authorization-check --no-interaction --no-ansi',
            '/usr/bin/php8.4 /release/current/artisan up --no-interaction --no-ansi',
        ], $result['commands']);
    }

    public function test_pre_mutation_activation_failure_reopens_the_quiesced_current_release_without_resynchronizing(): void
    {
        $result = $this->runActivationRecoveryHarness('before-mutation');

        $this->assertFalse($result['process']->isSuccessful());
        $this->assertSame([
            '/usr/bin/php8.4 /release/current/artisan up --no-interaction --no-ansi',
        ], $result['commands']);
    }

    public function test_activation_recovery_skips_first_same_release_and_post_switch_failures(): void
    {
        foreach ([
            'first-release',
            'same-release',
            'post-switch',
        ] as $mode) {
            $result = $this->runActivationRecoveryHarness($mode);

            $this->assertFalse($result['process']->isSuccessful(), $mode);
            $this->assertSame([], $result['commands'], $mode);
        }
    }

    public function test_activation_recovery_fails_closed_when_current_authorization_cannot_be_restored(): void
    {
        $result = $this->runActivationRecoveryHarness(
            'pre-switch-failure',
            'myapes:authorization-sync',
        );

        $this->assertFalse($result['process']->isSuccessful());
        $this->assertStringContainsString(
            'Current authorization could not be restored; maintenance mode remains active.',
            $result['process']->getOutput().$result['process']->getErrorOutput(),
        );
        $this->assertCount(3, $result['commands']);
    }

    public function test_activation_recovery_keeps_current_release_down_when_its_module_contract_is_unrepresentable(): void
    {
        $result = $this->runActivationRecoveryHarness(
            'pre-switch-failure',
            'myapes:modules:rollback-check',
        );

        $this->assertFalse($result['process']->isSuccessful());
        $this->assertStringContainsString(
            'Current release cannot represent the migrated module state; maintenance mode remains active.',
            $result['process']->getOutput().$result['process']->getErrorOutput(),
        );
        $this->assertSame([
            '/usr/bin/php8.4 /release/new/artisan myapes:modules:rollback-check --target-release=/release/current --no-interaction --no-ansi',
        ], $result['commands']);
    }

    public function test_activation_recovery_leaves_maintenance_active_when_the_current_release_cannot_reopen(): void
    {
        $result = $this->runActivationRecoveryHarness(
            'pre-switch-failure',
            ' up --no-interaction',
        );

        $this->assertFalse($result['process']->isSuccessful());
        $this->assertStringContainsString(
            'Current release could not leave maintenance mode after activation failure.',
            $result['process']->getOutput().$result['process']->getErrorOutput(),
        );
        $this->assertCount(6, $result['commands']);
    }

    public function test_activation_arms_recovery_before_database_mutation_and_commits_only_after_the_atomic_switch(): void
    {
        $script = $this->read('scripts/deploy/activate-release.sh');
        $trap = $this->position(
            $script,
            'trap restore_current_authorization_after_failure EXIT',
        );
        $maintenance = $this->position(
            $script,
            'run_current_artisan down --retry=60 --no-interaction --no-ansi',
        );
        $mutation = $this->position(
            $script,
            'PRE_SWITCH_DATABASE_MUTATED=true',
        );
        $migration = $this->position($script, 'run_artisan migrate --force');
        $switch = $this->position(
            $script,
            'mv -Tf "${CURRENT_LINK}.next" "$CURRENT_LINK"',
        );
        $committed = $this->position($script, 'ACTIVATION_SWITCHED=true');
        $reopened = $this->position(
            $script,
            'run_artisan up --no-interaction --no-ansi',
        );

        $this->assertLessThan($maintenance, $trap);
        $this->assertLessThan($mutation, $maintenance);
        $this->assertLessThan($migration, $mutation);
        $this->assertLessThan($switch, $migration);
        $this->assertLessThan($committed, $switch);
        $this->assertLessThan($reopened, $committed);
    }

    public function test_activation_installs_public_storage_link_as_root_before_artisan(): void
    {
        $script = $this->read('scripts/deploy/activate-release.sh');

        $storageLink = $this->position(
            $script,
            'install_public_storage_link "$RELEASE_DIR"',
        );
        $firstArtisan = $this->position($script, 'run_artisan optimize:clear');

        $this->assertLessThan($firstArtisan, $storageLink);
        $this->assertStringContainsString(
            'PUBLIC_STORAGE_LINK="${release_root}/public/storage"',
            $script,
        );
        $this->assertStringContainsString(
            'SHARED_PUBLIC_STORAGE="${SHARED_DIR}/storage/app/public"',
            $script,
        );
        $this->assertStringContainsString(
            'ln -s "$SHARED_PUBLIC_STORAGE" "$PUBLIC_STORAGE_LINK"',
            $script,
        );
        $this->assertStringContainsString(
            'sudo -u www-data test -w "${release_root}/public"',
            $script,
        );
        $this->assertStringNotContainsString('storage:link --force', $script);
        $this->assertStringNotContainsString('run_artisan storage:link', $script);
    }

    public function test_activation_requires_the_complete_phase_b_runtime_payload(): void
    {
        $script = $this->read('scripts/deploy/activate-release.sh');
        $requiredPaths = $this->bashArray($script, 'required_paths');

        $this->assertSame([
            'VERSION',
            'REVISION',
            'artisan',
            'public/index.php',
            'vendor/autoload.php',
            'public/build/manifest.json',
            'resources/data/releases.json',
            'resources/data/module-runtime-contract.json',
            'config/modules.php',
            'config/permission.php',
            'database/migrations/2026_07_28_000000_create_permission_tables.php',
            'database/migrations/2026_07_28_000100_cut_over_authorization_domain.php',
            'database/migrations/2026_08_06_000000_create_module_installations_table.php',
            'app/Console/Commands/AuthorizationPreflight.php',
            'app/Console/Commands/DirectorySync.php',
            'app/Console/Commands/AuthorizationSync.php',
            'app/Console/Commands/AuthorizationCheck.php',
            'app/Console/Commands/ModulesPreflight.php',
            'app/Console/Commands/ModulesSync.php',
            'app/Console/Commands/ModulesCheck.php',
            'app/Console/Commands/ModulesRollbackCheck.php',
            'app/Services/ModuleRollbackCompatibilityChecker.php',
            'scripts/deploy/activate-release.sh',
            'scripts/deploy/rollback-release.sh',
            'scripts/deploy/cloudron-app.conf',
            'scripts/deploy/cloudron-run.sh',
            'scripts/deploy/production.env.example',
            'DEPLOYMENT-CONTROLS.sha256',
        ], $requiredPaths);
    }

    public function test_workflow_restarts_after_activation_and_rolls_back_restart_or_verification_failures(): void
    {
        $workflow = $this->read('.github/workflows/deploy-cloudron.yml');
        $deploymentSources = $workflow."\n".$this->deploymentScripts();

        $activation = $this->position($workflow, '- name: Extract and activate uploaded release');
        $restart = $this->position($workflow, '- name: Restart activated release');
        $runtimeControls = $this->position($workflow, '- name: Reconstitute authenticated runtime controls after restart');
        $verify = $this->position($workflow, '- name: Verify exact release health and OIDC redirect');
        $rollback = $this->position($workflow, '- name: Roll back code after failed release restart or verification');

        $this->assertLessThan($restart, $activation);
        $this->assertLessThan($runtimeControls, $restart);
        $this->assertLessThan($verify, $runtimeControls);
        $this->assertLessThan($rollback, $verify);
        $this->assertMatchesRegularExpression(
            '/name:\s*Restart activated release\R\s+id:\s*restart\R\s+if:\s*steps\.activate\.outcome\s*==\s*[\'"]success[\'"]\R\s+continue-on-error:\s*true/s',
            $workflow,
        );
        $this->assertMatchesRegularExpression(
            '/\bid:\s*verify\s*\R(?:(?!\bid:).){0,300}?continue-on-error:\s*true/s',
            $workflow,
        );
        $this->assertMatchesRegularExpression(
            '/\bif:\s*(?:\$\{\{\s*)?steps\.restart\.outcome\s*==\s*[\'"]success[\'"]\s*&&\s*steps\.runtime_controls\.outcome\s*==\s*[\'"]success[\'"](?:\s*\}\})?/',
            $workflow,
        );
        $this->assertStringContainsString('steps.runtime_controls.outcome', $workflow);
        $this->assertStringContainsString(
            "steps.recovery.outputs.rollback_required == 'true'",
            $workflow,
        );
        $this->assertStringContainsString(
            'bash "$remote_dir/scripts/deploy/activate-release.sh"',
            $workflow,
        );
        $this->assertStringContainsString(
            '"$release_sha" "$previous_release" "$expected_controls_sha256"',
            $workflow,
        );
        $this->assertStringContainsString(
            'bash "$control_root/scripts/deploy/rollback-release.sh"',
            $workflow,
        );
        $this->assertStringContainsString(
            '"$previous_release" "$failed_release" "$expected_controls_sha256" "$control_root"',
            $workflow,
        );
        $this->assertStringContainsString('previous_version', $workflow);
        $this->assertStringContainsString('previous_release', $workflow);
        $this->assertStringContainsString('Verify restored release health and OIDC redirect', $workflow);

        $this->assertStringContainsString('/healthz', $workflow);
        $this->assertStringContainsString('reported_version', $workflow);
        $this->assertStringContainsString('APP_VERSION', $workflow);
        $this->assertStringContainsString('reported_database', $workflow);
        $this->assertStringContainsString('reported_cache', $workflow);
        $this->assertStringContainsString('reported_environment', $workflow);
        $this->assertStringContainsString('/staff/auth/login', $deploymentSources);
        $this->assertStringContainsString('/staff/auth/callback', $deploymentSources);
        $this->assertStringContainsString('CLOUDRON_OIDC_AUTH_URL', $deploymentSources);
        $this->assertStringContainsString('response_type', $deploymentSources);
        $this->assertStringContainsString('state', $deploymentSources);
        $this->assertStringContainsString('nonce', $deploymentSources);
        $this->assertStringContainsString('code_challenge_method', $deploymentSources);
        $this->assertStringContainsString('code_challenge', $deploymentSources);
        $this->assertStringContainsString('S256', $deploymentSources);
        $this->assertStringContainsString('openid', $deploymentSources);
        $this->assertStringContainsString('profile', $deploymentSources);
        $this->assertStringContainsString('email', $deploymentSources);

        $normalizedWorkflow = preg_replace('/\\\\\R\s*/', ' ', $workflow) ?? $workflow;
        $this->assertDoesNotMatchRegularExpression(
            '/curl[^\r\n]*(?:\s-L(?:\s|$)|\s--location(?:\s|$))/m',
            $normalizedWorkflow,
        );
    }

    public function test_workflow_validates_versions_on_pull_requests_without_deploying_them(): void
    {
        $workflow = $this->read('.github/workflows/deploy-cloudron.yml');
        $deployJob = substr($workflow, $this->position($workflow, '  deploy:'), 500);

        $this->assertMatchesRegularExpression('/pull_request:\s*\R/', $workflow);
        $this->assertStringContainsString('fetch-depth: 0', $workflow);
        $this->assertStringContainsString('github.event.pull_request.base.sha', $workflow);
        $this->assertStringContainsString('github.event.before', $workflow);
        $this->assertStringContainsString('myapes:changelog-validate', $workflow);
        $this->assertStringContainsString('npm run test:frontend', $workflow);
        $this->assertMatchesRegularExpression(
            '/deploy:\s*\R(?:(?!\n\S).)*if:\s*\$\{\{\s*github\.event_name\s*==\s*[\'"]push[\'"]\s*&&\s*github\.ref\s*==\s*[\'"]refs\/heads\/main[\'"]\s*\}\}/s',
            $deployJob,
        );
    }

    public function test_workflow_pins_supported_node_24_first_party_actions(): void
    {
        $workflow = $this->read('.github/workflows/deploy-cloudron.yml');

        foreach ([
            'actions/checkout@3d3c42e5aac5ba805825da76410c181273ba90b1 # v7.0.1' => 2,
            'actions/setup-node@820762786026740c76f36085b0efc47a31fe5020 # v7.0.0' => 1,
            'actions/upload-artifact@043fb46d1a93c77aae656e7c1c64a875d1fc6a0a # v7.0.1' => 1,
            'actions/download-artifact@3e5f45b2cfb9172054b4087a40e8e0b5a5461e7c # v8.0.1' => 1,
        ] as $reference => $expectedOccurrences) {
            $this->assertSame(
                $expectedOccurrences,
                substr_count($workflow, $reference),
                "Unexpected occurrence count for {$reference}.",
            );
        }

        foreach ([
            'actions/checkout@v4',
            'actions/setup-node@v4',
            'actions/upload-artifact@v4',
            'actions/download-artifact@v4',
        ] as $deprecatedReference) {
            $this->assertStringNotContainsString($deprecatedReference, $workflow);
        }
    }

    public function test_database_compatibility_job_allows_forward_only_contract_to_finish(): void
    {
        $workflow = $this->read('.github/workflows/deploy-cloudron.yml');
        $databaseCompatibilityStart = $this->position(
            $workflow,
            '  database-compatibility:',
        );
        $deployStart = $this->position($workflow, '  deploy:');
        $databaseCompatibilityJob = substr(
            $workflow,
            $databaseCompatibilityStart,
            $deployStart - $databaseCompatibilityStart,
        );

        preg_match_all(
            '/^\s*timeout-minutes:\s*\d+\s*$/m',
            $databaseCompatibilityJob,
            $timeoutSettings,
        );

        $this->assertCount(1, $timeoutSettings[0]);
        $this->assertMatchesRegularExpression(
            '/^\s*timeout-minutes:\s*30\s*$/m',
            $databaseCompatibilityJob,
        );
    }

    public function test_workflow_runs_the_phase_b_contract_on_mysql_and_mariadb(): void
    {
        $workflow = $this->read('.github/workflows/deploy-cloudron.yml');
        $deployJob = substr($workflow, $this->position($workflow, '  deploy:'), 500);

        $this->assertStringContainsString('database-compatibility:', $workflow);
        $this->assertStringContainsString('fail-fast: false', $workflow);
        $this->assertStringContainsString('image: mysql:8.4', $workflow);
        $this->assertStringContainsString('image: mariadb:11.4', $workflow);
        $this->assertStringContainsString('php-version: \'8.4\'', $workflow);
        $this->assertStringContainsString('pdo_mysql', $workflow);
        $this->assertStringContainsString('pcntl', $workflow);
        $this->assertStringContainsString('MYSQL_DATABASE: myapes_test', $workflow);
        $this->assertStringContainsString('MARIADB_DATABASE: myapes_test', $workflow);
        $this->assertStringContainsString('mysqladmin ping', $workflow);
        $this->assertStringContainsString('healthcheck.sh --connect --innodb_initialized', $workflow);
        $this->assertStringContainsString('APP_MAINTENANCE_DRIVER: file', $workflow);
        $this->assertStringContainsString('APP_MAINTENANCE_STORE: file', $workflow);
        $this->assertStringContainsString('CACHE_STORE: database', $workflow);

        foreach ([
            'AuthorizationSchemaTest.php',
            'AuthorizationRoleMaterializerTest.php',
            'AuthorizationCutoverGuardTest.php',
            'AuthorizationCutoverConcurrencyTest.php',
            'PermissionSchemaMigrationTest.php',
            'AccessCompatibilityMigrationTest.php',
            'AccessCompatibilityCommandTest.php',
            'RolelessApplicationCompatibilityTest.php',
            'UserAccessCompatibilityTest.php',
            'AuthorizationLifecycleCommandTest.php',
            'DirectoryCatalogueSynchronizerTest.php',
            'DirectorySyncWiringTest.php',
            'Auth/DirectoryRevalidationTest.php',
            'DirectoryGroupMappingServiceTest.php',
            'DirectoryRoleSynchronizerTest.php',
            'ModuleRegistryTest.php',
            'ModuleInstallationSynchronizationTest.php',
            'ModuleLifecycleTest.php',
            'ModuleLifecycleConcurrencyTest.php',
            'ModuleRollbackCompatibilityTest.php',
        ] as $testFile) {
            $this->assertStringContainsString(
                "tests/Feature/{$testFile}",
                $workflow,
            );
        }

        $this->assertStringContainsString(
            'tests/Support/directory-sync-timeout-probe.php prepare',
            $workflow,
        );
        $this->assertStringContainsString(
            'tests/Support/directory-sync-timeout-probe.php assert',
            $workflow,
        );
        $this->assertStringContainsString(
            '--queue=security-timeout',
            $workflow,
        );
        $this->assertStringContainsString(
            '--stop-when-empty',
            $workflow,
        );
        $this->assertStringNotContainsString(
            '--once',
            $workflow,
        );

        $this->assertMatchesRegularExpression(
            '/deploy:\s*\R(?:(?!\n\S).)*needs:\s*\[\s*deployment-control-authentication,\s*quality,\s*database-compatibility\s*\]/s',
            $deployJob,
        );
    }

    public function test_workflow_isolates_the_destructive_foundation_migration_contract(): void
    {
        $workflow = $this->read('.github/workflows/deploy-cloudron.yml');
        $databaseCompatibilityJob = substr(
            $workflow,
            $this->position($workflow, '  database-compatibility:'),
            $this->position($workflow, '  deploy:')
                - $this->position($workflow, '  database-compatibility:'),
        );
        $foundationMigrationTest = 'tests/Feature/ApesCicFoundationMigrationTest.php';
        $standaloneFoundationCommand = "php artisan test {$foundationMigrationTest}";
        $destructiveTestResetCommand = 'php artisan migrate:fresh --force --no-interaction';
        $standaloneFoundationPosition = $this->position(
            $databaseCompatibilityJob,
            $standaloneFoundationCommand,
        );
        $destructiveTestResetPosition = $this->position(
            $databaseCompatibilityJob,
            $destructiveTestResetCommand,
        );
        $mainSuitePosition = $this->position(
            $databaseCompatibilityJob,
            "php artisan test \\\n",
        );
        $mainSuite = substr(
            $databaseCompatibilityJob,
            $mainSuitePosition,
            $this->position(
                $databaseCompatibilityJob,
                'php artisan migrate:fresh --seed --force --no-interaction',
            ) - $mainSuitePosition,
        );

        $this->assertSame(1, substr_count($databaseCompatibilityJob, $foundationMigrationTest));
        $this->assertMatchesRegularExpression(
            '/^\s*php artisan test\s+tests\/Feature\/ApesCicFoundationMigrationTest\.php\s*$/m',
            $databaseCompatibilityJob,
        );
        $this->assertSame(1, substr_count(
            $databaseCompatibilityJob,
            $destructiveTestResetCommand,
        ));
        $this->assertLessThan($destructiveTestResetPosition, $standaloneFoundationPosition);
        $this->assertLessThan($mainSuitePosition, $destructiveTestResetPosition);
        $this->assertStringNotContainsString($foundationMigrationTest, $mainSuite);
    }

    public function test_workflow_packages_and_verifies_semantic_and_commit_identities(): void
    {
        $workflow = $this->read('.github/workflows/deploy-cloudron.yml');

        $this->assertStringContainsString('app_version:', $workflow);
        $this->assertStringContainsString('app_version=', $workflow);
        $this->assertStringContainsString("grep -qx './VERSION' build/archive-list.txt", $workflow);
        foreach ([
            'resources/data/releases.json',
            'resources/data/module-runtime-contract.json',
            'config/modules.php',
            'config/permission.php',
            'database/migrations/2026_07_28_000000_create_permission_tables.php',
            'database/migrations/2026_07_28_000100_cut_over_authorization_domain.php',
            'database/migrations/2026_08_06_000000_create_module_installations_table.php',
            'app/Console/Commands/AuthorizationPreflight.php',
            'app/Console/Commands/DirectorySync.php',
            'app/Console/Commands/AuthorizationSync.php',
            'app/Console/Commands/AuthorizationCheck.php',
            'app/Console/Commands/ModulesPreflight.php',
            'app/Console/Commands/ModulesSync.php',
            'app/Console/Commands/ModulesCheck.php',
            'app/Console/Commands/ModulesRollbackCheck.php',
            'app/Services/ModuleRollbackCompatibilityChecker.php',
            'scripts/deploy/activate-release.sh',
            'scripts/deploy/rollback-release.sh',
            'scripts/deploy/cloudron-app.conf',
            'scripts/deploy/cloudron-run.sh',
            'scripts/deploy/production.env.example',
            'DEPLOYMENT-CONTROLS.sha256',
        ] as $requiredPath) {
            $this->assertStringContainsString(
                "grep -qx './{$requiredPath}' build/archive-list.txt",
                $workflow,
            );
        }
        $this->assertStringContainsString('reported_version', $workflow);
        $this->assertStringContainsString('reported_release', $workflow);
        $this->assertStringContainsString(
            '"$reported_version" == "$APP_VERSION"',
            $workflow,
        );
        $this->assertStringContainsString(
            '"$reported_release" == "$RELEASE_SHA"',
            $workflow,
        );
        $this->assertStringContainsString(
            '[[ "$reported_version" =~ ^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$ ]]',
            $workflow,
        );
        $this->assertStringContainsString(
            '[[ "$reported_release" =~ ^[0-9a-f]{40}$ ]]',
            $workflow,
        );
    }

    public function test_workflow_authenticates_every_deployment_control_before_execution(): void
    {
        $workflow = $this->read('.github/workflows/deploy-cloudron.yml');
        $activation = $this->read('scripts/deploy/activate-release.sh');
        $rollback = $this->read('scripts/deploy/rollback-release.sh');

        $this->assertStringContainsString(
            'deployment_controls_sha256:',
            $workflow,
        );
        $this->assertStringContainsString(
            'DEPLOYMENT-CONTROLS.sha256',
            $workflow,
        );
        $this->assertStringContainsString(
            'DEPLOYMENT_CONTROLS_SHA256',
            $workflow,
        );
        $this->assertStringContainsString(
            'sha256sum --check --strict',
            $workflow,
        );
        $this->assertGreaterThanOrEqual(
            3,
            substr_count($workflow, 'test ! -L'),
        );
        $this->assertLessThan(
            strpos(
                $workflow,
                'bash "$remote_dir/scripts/deploy/activate-release.sh"',
            ),
            strpos(
                $workflow,
                'test ! -L "$remote_dir/$control_path"',
            ),
        );
        $this->assertLessThan(
            strpos(
                $workflow,
                'bash "$control_root/scripts/deploy/rollback-release.sh"',
            ),
            strpos(
                $workflow,
                'test ! -L "$control_root/$control_path"',
            ),
        );
        $this->assertStringContainsString(
            'EXPECTED_CONTROLS_SHA256="${3:-}"',
            $activation,
        );
        $this->assertStringContainsString(
            'EXPECTED_CONTROLS_SHA256="${3:-}"',
            $rollback,
        );
        $this->assertStringContainsString(
            'verify_deployment_controls',
            $activation,
        );
        $this->assertStringContainsString(
            'verify_deployment_controls',
            $rollback,
        );
    }

    public function test_deployment_control_trust_is_derived_before_third_party_actions_and_dependencies(): void
    {
        $workflow = $this->read('.github/workflows/deploy-cloudron.yml');
        $authenticationStart = $this->position(
            $workflow,
            'deployment-control-authentication:',
        );
        $qualityStart = $this->position($workflow, '  quality:');
        $authenticationJob = substr(
            $workflow,
            $authenticationStart,
            $qualityStart - $authenticationStart,
        );

        $this->assertLessThan($qualityStart, $authenticationStart);
        $this->assertStringNotContainsString('uses:', $authenticationJob);
        $this->assertStringNotContainsString('composer ', $authenticationJob);
        $this->assertStringNotContainsString('npm ', $authenticationJob);
        $this->assertStringContainsString(
            'git -C "$repository_root" show',
            $authenticationJob,
        );
        foreach ([
            'scripts/deploy/activate-release.sh',
            'scripts/deploy/rollback-release.sh',
            'scripts/deploy/cloudron-app.conf',
            'scripts/deploy/cloudron-run.sh',
        ] as $controlPath) {
            $this->assertStringContainsString($controlPath, $authenticationJob);
        }
        $this->assertMatchesRegularExpression(
            '/needs:\s*\[\s*deployment-control-authentication,\s*quality,\s*database-compatibility\s*\]/',
            $workflow,
        );
        $this->assertStringContainsString(
            'needs.deployment-control-authentication.outputs.deployment_controls_sha256',
            $workflow,
        );
        $this->assertStringNotContainsString(
            'needs.quality.outputs.deployment_controls_sha256',
            $workflow,
        );
    }

    public function test_activation_and_rollback_consume_root_owned_control_copies(): void
    {
        $workflow = $this->read('.github/workflows/deploy-cloudron.yml');
        $activation = $this->read('scripts/deploy/activate-release.sh');
        $rollback = $this->read('scripts/deploy/rollback-release.sh');

        $this->assertStringContainsString(
            'CONTROL_RELEASES_DIR="/run/myapes-deployment-controls"',
            $activation,
        );
        $this->assertStringContainsString(
            'CONTROL_RELEASE_DIR="${CONTROL_RELEASES_DIR}/${RELEASE_SHA}"',
            $activation,
        );
        $this->assertStringContainsString(
            'install -d -o root -g root -m 0700 "$CONTROL_RELEASES_DIR"',
            $activation,
        );
        $this->assertStringContainsString(
            'assert_hardened_control_directory "$CONTROL_RELEASE_DIR"',
            $activation,
        );
        $this->assertStringNotContainsString(
            'chown -R www-data:www-data "$TEMP_RELEASE_DIR"',
            $activation,
        );
        $this->assertStringContainsString(
            'verify_deployment_controls "$CONTROL_RELEASE_DIR" "$EXPECTED_CONTROLS_SHA256"',
            $activation,
        );
        $this->assertStringContainsString(
            'CONTROL_RELEASES_DIR="/run/myapes-deployment-controls"',
            $rollback,
        );
        $this->assertStringContainsString('CONTROL_ROOT="${4:-}"', $rollback);
        $this->assertStringContainsString(
            '"$CONTROL_ROOT" != "${CONTROL_RELEASES_DIR}/${EXPECTED_CURRENT_SHA}"',
            $rollback,
        );
        $this->assertStringContainsString(
            'verify_deployment_controls "$CONTROL_ROOT" "$EXPECTED_CONTROLS_SHA256"',
            $rollback,
        );
        $this->assertStringContainsString(
            '"${CONTROL_ROOT}/scripts/deploy/cloudron-app.conf"',
            $rollback,
        );
        $this->assertStringContainsString(
            '"${CONTROL_ROOT}/scripts/deploy/cloudron-run.sh"',
            $rollback,
        );
        $this->assertStringContainsString(
            'bash "$control_root/scripts/deploy/rollback-release.sh"',
            $workflow,
        );
        $this->assertStringNotContainsString('/app/data/deployment-controls', $workflow.$activation.$rollback);
    }

    public function test_post_restart_controls_are_reauthenticated_outside_app_data_and_write_boundaries_are_checked(): void
    {
        $workflow = $this->read('.github/workflows/deploy-cloudron.yml');
        $activation = $this->read('scripts/deploy/activate-release.sh');

        $this->assertStringContainsString(
            '- name: Reconstitute authenticated runtime controls after restart',
            $workflow,
        );
        $this->assertStringContainsString('id: runtime_controls', $workflow);
        $this->assertStringContainsString(
            'control_root="/run/myapes-deployment-controls/$release_sha"',
            $workflow,
        );
        $this->assertGreaterThanOrEqual(2, substr_count(
            $workflow,
            'tar --extract --gzip',
        ));
        $this->assertGreaterThanOrEqual(2, substr_count(
            $workflow,
            'sha256sum --check --strict DEPLOYMENT-CONTROLS.sha256',
        ));
        $this->assertStringContainsString(
            'sudo -u www-data test -w "$control_root"',
            $workflow,
        );
        foreach ([
            '/app/data',
            '/app/data/current',
            '/app/data/current/public',
            '/app/data/run.sh',
            '/app/data/apache/app.conf',
        ] as $protectedPath) {
            $this->assertStringContainsString(
                'sudo -u www-data test -w '.$protectedPath,
                $workflow,
            );
        }
        $this->assertStringContainsString('chmod -R a-w -- "$release_root"', $activation);
        $this->assertStringContainsString('chmod 0555 "$DATA_DIR" "$RELEASES_DIR"', $activation);
        $this->assertStringContainsString(
            'harden_release_write_boundaries "$CURRENT_TARGET_BEFORE"',
            $activation,
        );
        $this->assertStringNotContainsString('rm -rf -- "$DEPLOY_DIR"', $activation);
    }

    public function test_cloudron_restart_restores_root_ownership_before_application_code_runs(): void
    {
        $workflow = $this->read('.github/workflows/deploy-cloudron.yml');
        $activation = $this->read('scripts/deploy/activate-release.sh');
        $rollback = $this->read('scripts/deploy/rollback-release.sh');
        $launcher = $this->read('scripts/deploy/cloudron-run.sh');

        $ownershipRestore = $this->position(
            $workflow,
            'restore_runtime_ownership "$release_sha"',
        );
        $archiveRead = $this->position(
            $workflow,
            'test -f "$archive_path"',
        );
        $this->assertLessThan($archiveRead, $ownershipRestore);
        $this->assertStringContainsString(
            'chown -hR root:root "$release_root"',
            $workflow,
        );
        $this->assertStringContainsString(
            'chown root:root /app/data /app/data/releases /app/data/shared /app/data/apache',
            $workflow,
        );
        $this->assertStringContainsString(
            'chown root:www-data /app/data/shared/.env',
            $workflow,
        );
        $this->assertStringContainsString(
            'chown -hR www-data:www-data /app/data/shared/storage',
            $workflow,
        );
        $this->assertStringContainsString(
            "if: always() && steps.restart.outcome != 'skipped'",
            $workflow,
        );

        foreach ([$workflow, $activation, $rollback, $launcher] as $source) {
            $this->assertStringContainsString(
                "stat -Lc '%U:%G'",
                $source,
            );
            $this->assertStringContainsString('root:root', $source);
        }

        foreach ([$workflow, $activation, $rollback] as $source) {
            $this->assertStringContainsString(
                '! -path "${release_root}/bootstrap/cache"',
                $source,
            );
            $this->assertStringContainsString(
                '! -path "${release_root}/bootstrap/cache/*"',
                $source,
            );
        }

        $this->assertStringContainsString(
            'chown -hR root:root "$release_root"',
            $activation,
        );
        $parentOwnership = $this->position(
            $activation,
            'chown root:root "$DATA_DIR" "$RELEASES_DIR"',
        );
        $releaseHardening = $this->position(
            $activation,
            'harden_release_write_boundaries "$RELEASE_DIR"',
        );
        $this->assertLessThan($releaseHardening, $parentOwnership);
        $this->assertStringContainsString(
            'chown -hR root:root "$ROLLBACK_TARGET"',
            $rollback,
        );
        $this->assertStringContainsString(
            'Application runtime ownership is not root-controlled.',
            $launcher,
        );
        $this->assertStringContainsString('chown() {', $launcher);
        $this->assertStringContainsString('command chown "$@"', $launcher);
        $this->assertStringContainsString(
            'restore_runtime_ownership',
            $launcher,
        );
        $this->assertStringContainsString(
            'start_laravel_runtime',
            $launcher,
        );
        $this->assertStringContainsString(
            'MYAPES_OWNERSHIP_RESTORED=true',
            $launcher,
        );
        $this->assertStringContainsString('exec() {', $launcher);
        $this->assertStringContainsString(
            'Refusing process replacement before root ownership restoration.',
            $launcher,
        );
        $this->assertStringContainsString(
            '"$3" != "/app/data" || "$4" != "/run/apache2"',
            $launcher,
        );
        foreach ([$workflow, $launcher] as $source) {
            $this->assertStringContainsString(
                "-path '/app/data/releases/*/bootstrap/cache' -prune",
                $source,
            );
        }
        $this->assertStringContainsString(
            '-path "${RELEASES_DIR}/*/bootstrap/cache" -prune',
            $activation,
        );
        $this->assertStringContainsString(
            'sudo -u www-data test -w "${CURRENT_DIR}/bootstrap/cache"',
            $launcher,
        );
        $this->assertStringContainsString(
            'sudo -u www-data test -w /app/data/shared/storage',
            $launcher,
        );
    }

    public function test_control_verifiers_reject_marker_preserving_tampering_and_unsafe_manifests(): void
    {
        $temporaryRoot = sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'myapes-control-verifier-'.bin2hex(random_bytes(8));
        $this->assertTrue(mkdir(
            $temporaryRoot.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'deploy',
            0700,
            true,
        ));

        try {
            $controlPaths = [
                'scripts/deploy/activate-release.sh',
                'scripts/deploy/rollback-release.sh',
                'scripts/deploy/cloudron-app.conf',
                'scripts/deploy/cloudron-run.sh',
            ];
            foreach ($controlPaths as $relativePath) {
                $target = $temporaryRoot.DIRECTORY_SEPARATOR.str_replace(
                    '/',
                    DIRECTORY_SEPARATOR,
                    $relativePath,
                );
                $this->assertTrue(copy($this->path($relativePath), $target));
            }
            $manifestPath = $temporaryRoot.DIRECTORY_SEPARATOR
                .'DEPLOYMENT-CONTROLS.sha256';
            $this->writeControlManifest(
                $temporaryRoot,
                $manifestPath,
                $controlPaths,
            );
            $manifestHash = hash_file('sha256', $manifestPath);
            $this->assertIsString($manifestHash);

            foreach (['activate-release.sh', 'rollback-release.sh'] as $script) {
                $this->runControlVerifier(
                    $temporaryRoot,
                    $script,
                    $manifestHash,
                )->mustRun();
            }

            file_put_contents(
                $temporaryRoot.DIRECTORY_SEPARATOR.'scripts'
                    .DIRECTORY_SEPARATOR.'deploy'.DIRECTORY_SEPARATOR
                    .'cloudron-app.conf',
                "\n# marker-preserving tamper\n",
                FILE_APPEND,
            );
            foreach (['activate-release.sh', 'rollback-release.sh'] as $script) {
                $this->assertControlVerifierFails(
                    $temporaryRoot,
                    $script,
                    $manifestHash,
                );
            }

            copy(
                $this->path('scripts/deploy/cloudron-app.conf'),
                $temporaryRoot.DIRECTORY_SEPARATOR.'scripts'
                    .DIRECTORY_SEPARATOR.'deploy'.DIRECTORY_SEPARATOR
                    .'cloudron-app.conf',
            );
            $unsafeManifest = hash_file(
                'sha256',
                $temporaryRoot.DIRECTORY_SEPARATOR.'scripts'
                    .DIRECTORY_SEPARATOR.'deploy'.DIRECTORY_SEPARATOR
                    .'activate-release.sh',
            )."  ../outside-control\n";
            file_put_contents($manifestPath, $unsafeManifest);
            $unsafeManifestHash = hash_file('sha256', $manifestPath);
            $this->assertIsString($unsafeManifestHash);

            foreach (['activate-release.sh', 'rollback-release.sh'] as $script) {
                $this->assertControlVerifierFails(
                    $temporaryRoot,
                    $script,
                    $unsafeManifestHash,
                );
                $this->assertControlVerifierFails(
                    $temporaryRoot,
                    $script,
                    str_repeat('0', 64),
                );
            }
        } finally {
            $this->removeTemporaryDirectory($temporaryRoot);
        }
    }

    public function test_release_exclusion_only_rejects_the_git_directory(): void
    {
        $workflow = $this->read('.github/workflows/deploy-cloudron.yml');

        $this->assertStringContainsString('\.git($|/)', $workflow);
        $this->assertStringNotContainsString('^\./(\.git|', $workflow);
    }

    public function test_production_environment_has_no_legacy_group_list_inputs(): void
    {
        $environment = $this->read('scripts/deploy/production.env.example');
        $activation = $this->read('scripts/deploy/activate-release.sh');
        $runScript = $this->read('scripts/deploy/cloudron-run.sh');
        $apache = $this->read('scripts/deploy/cloudron-app.conf');

        $this->assertStringNotContainsString('OIDC_STAFF_GROUPS', $environment);
        $this->assertStringNotContainsString('OIDC_ADMIN_GROUPS', $environment);
        $this->assertStringNotContainsString('OIDC_SUPERADMIN_GROUPS', $environment);
        $this->assertStringContainsString('APP_ENV=production', $environment);
        $this->assertStringContainsString(
            'sudo -E -u www-data env APP_ENV=production',
            $activation,
        );
        $this->assertStringContainsString(
            'sudo -E -u www-data env APP_ENV=production',
            $runScript,
        );
        $this->assertStringContainsString('export APP_ENV=production', $runScript);
        $this->assertStringContainsString('SetEnv APP_ENV production', $apache);
        foreach ([
            '/app/data',
            '/app/data/releases',
            '"$CURRENT_DIR"',
            '"${CURRENT_DIR}/public"',
            '/app/data/run.sh',
            '/app/data/apache/app.conf',
        ] as $protectedPath) {
            $this->assertStringContainsString($protectedPath, $runScript);
        }
        $this->assertStringContainsString(
            'sudo -u www-data test -w "$protected_path"',
            $runScript,
        );
        $this->assertStringContainsString(
            'sudo -u www-data test -w "${CURRENT_DIR}/bootstrap/cache"',
            $runScript,
        );
    }

    public function test_retained_worker_timeout_is_safe_for_v071_without_narrowing_the_phase_b_job(): void
    {
        $runScript = $this->read('scripts/deploy/cloudron-run.sh');
        $directoryJob = $this->read('app/Jobs/RunDirectorySync.php');
        $queueConfig = $this->read('config/queue.php');

        $this->assertStringContainsString('--timeout=60', $runScript);
        $this->assertStringNotContainsString('--timeout=240', $runScript);
        $this->assertStringContainsString(
            'public const TIMEOUT_SECONDS = 240;',
            $directoryJob,
        );
        $this->assertSame(
            3,
            substr_count(
                $queueConfig,
                "env('DB_QUEUE_RETRY_AFTER', 300)",
            )
                + substr_count(
                    $queueConfig,
                    "env('BEANSTALKD_QUEUE_RETRY_AFTER', 300)",
                )
                + substr_count(
                    $queueConfig,
                    "env('REDIS_QUEUE_RETRY_AFTER', 300)",
                ),
        );
    }

    public function test_first_release_does_not_resolve_absent_release_links(): void
    {
        $script = $this->read('scripts/deploy/activate-release.sh');

        $this->assertMatchesRegularExpression(
            '/if \[\[ -L "\$CURRENT_LINK" \]\]; then\R\s+CURRENT_TARGET_BEFORE="\$\(readlink -f/',
            $script,
        );
        $this->assertMatchesRegularExpression(
            '/if \[\[ -L "\$PREVIOUS_LINK" \]\]; then\R\s+PREVIOUS_TARGET_BEFORE="\$\(readlink -f/',
            $script,
        );
        $this->assertStringContainsString('CURRENT_TARGET_BEFORE=""', $script);
        $this->assertStringContainsString('PREVIOUS_TARGET_BEFORE=""', $script);
    }

    public function test_code_rollback_quiesces_mutations_reconciles_authorization_and_never_reverses_migrations(): void
    {
        $script = $this->read('scripts/deploy/rollback-release.sh');

        $this->assertStringContainsString('ROLLBACK_TARGET="$(readlink -f "$PREVIOUS_LINK")"', $script);
        $this->assertStringContainsString('"$ROLLBACK_TARGET" != "${RELEASES_DIR}/"*', $script);
        $this->assertStringContainsString(
            '"$ROLLBACK_SHA" != "$EXPECTED_ROLLBACK_SHA"',
            $script,
        );
        $this->assertStringContainsString(
            '"$CURRENT_SHA" != "$EXPECTED_CURRENT_SHA"',
            $script,
        );
        $this->assertStringContainsString(
            'Previous release lost its immutable write boundary',
            $script,
        );
        $this->assertStringContainsString(
            'install -m 0444 "${CONTROL_ROOT}/scripts/deploy/cloudron-app.conf"',
            $script,
        );
        $this->assertStringContainsString(
            'install -m 0555 "${CONTROL_ROOT}/scripts/deploy/cloudron-run.sh"',
            $script,
        );
        $this->assertStringNotContainsString(
            'install -m 0644 "${ROLLBACK_TARGET}/scripts/deploy/cloudron-app.conf"',
            $script,
        );
        $this->assertStringNotContainsString(
            'install -m 0755 "${ROLLBACK_TARGET}/scripts/deploy/cloudron-run.sh"',
            $script,
        );
        $this->assertStringContainsString(
            'SetEnv[[:space:]]+APP_ENV[[:space:]]+production',
            $script,
        );
        $this->assertStringContainsString(
            'export APP_ENV=production',
            $script,
        );
        $this->assertStringContainsString(
            'env APP_ENV=production',
            $script,
        );
        $runtimeStaging = $this->position(
            $script,
            'install -m 0444 "${CONTROL_ROOT}/scripts/deploy/cloudron-app.conf"',
        );
        $moduleCompatibility = $this->position(
            $script,
            'myapes:modules:rollback-check',
        );
        $maintenanceStart = $this->position(
            $script,
            '"${CURRENT_TARGET}/artisan" down --retry=60 --no-interaction --no-ansi',
        );
        $rollbackAuthorizationSync = $this->position(
            $script,
            '"${ROLLBACK_TARGET}/artisan" myapes:authorization-sync --no-interaction --no-ansi',
        );
        $rollbackAuthorizationCheck = $this->position(
            $script,
            '"${ROLLBACK_TARGET}/artisan" myapes:authorization-check --no-interaction --no-ansi',
        );
        $firstRollbackMutation = $this->position(
            $script,
            'rm -f -- "$target_path"',
        );
        $applicationSwitch = $this->position(
            $script,
            'mv -Tf "${CURRENT_LINK}.rollback" "$CURRENT_LINK"',
        );
        $runtimePublish = $this->position(
            $script,
            'mv -Tf "${DATA_DIR}/apache/app.conf.rollback" "${DATA_DIR}/apache/app.conf"',
        );
        $this->assertLessThan($applicationSwitch, $runtimeStaging);
        $this->assertLessThan($runtimePublish, $applicationSwitch);
        $this->assertLessThan($moduleCompatibility, $maintenanceStart);
        $this->assertLessThan(
            $rollbackAuthorizationSync,
            $moduleCompatibility,
        );
        $this->assertLessThan(
            $rollbackAuthorizationCheck,
            $rollbackAuthorizationSync,
        );
        $this->assertLessThan(
            $applicationSwitch,
            $rollbackAuthorizationCheck,
        );
        $this->assertLessThan($firstRollbackMutation, $moduleCompatibility);
        $this->assertStringContainsString(
            '"${CURRENT_TARGET}/artisan" myapes:modules:rollback-check',
            $script,
        );
        $this->assertStringContainsString(
            '--target-release="$ROLLBACK_TARGET" --no-interaction --no-ansi',
            $script,
        );
        $this->assertStringContainsString('ln -s "$ROLLBACK_TARGET" "${CURRENT_LINK}.rollback"', $script);
        $this->assertStringContainsString('mv -Tf "${CURRENT_LINK}.rollback" "$CURRENT_LINK"', $script);
        $this->assertStringContainsString('scripts/deploy/cloudron-app.conf', $script);
        $this->assertStringContainsString('scripts/deploy/cloudron-run.sh', $script);
        $this->assertStringContainsString(
            '"${ROLLBACK_TARGET}/artisan" up --no-interaction --no-ansi',
            $script,
        );
        $this->assertStringContainsString(
            '"${CURRENT_TARGET}/artisan" myapes:authorization-sync --no-interaction --no-ansi',
            $script,
        );
        $this->assertStringContainsString(
            'trap restore_current_release EXIT',
            $script,
        );
        $this->assertStringContainsString('Database migrations were retained', $script);
        $this->assertStringNotContainsString('migrate:rollback', $script);
        $this->assertStringNotContainsString('migrate:reset', $script);
        $this->assertStringNotContainsString('migrate:fresh', $script);
    }

    public function test_activation_target_classifier_covers_safe_recovery_states_and_rejects_ambiguous_links(): void
    {
        $newRelease = str_repeat('a', 40);
        $priorRelease = str_repeat('b', 40);
        $olderRelease = str_repeat('c', 40);
        $releaseRoot = '/app/data/releases';

        foreach ([
            'pre-switch' => [
                $newRelease,
                $priorRelease,
                'true',
                'false',
                "{$releaseRoot}/{$priorRelease}",
                "{$releaseRoot}/{$olderRelease}",
                $releaseRoot,
            ],
            'post-switch' => [
                $newRelease,
                $priorRelease,
                'true',
                'false',
                "{$releaseRoot}/{$newRelease}",
                "{$releaseRoot}/{$priorRelease}",
                $releaseRoot,
            ],
            'same-release' => [
                $newRelease,
                $newRelease,
                'true',
                'true',
                "{$releaseRoot}/{$newRelease}",
                "{$releaseRoot}/{$olderRelease}",
                $releaseRoot,
            ],
            'first-deployment' => [
                $newRelease,
                '',
                'false',
                'false',
                '',
                '',
                $releaseRoot,
            ],
            'unavailable-rollback' => [
                $newRelease,
                '',
                'false',
                'false',
                "{$releaseRoot}/{$newRelease}",
                '',
                $releaseRoot,
            ],
        ] as $expectedState => $arguments) {
            $process = $this->runActivationTargetClassifierHarness($arguments);

            $this->assertTrue(
                $process->isSuccessful(),
                $expectedState.': '.$process->getErrorOutput(),
            );
            $this->assertSame($expectedState, trim($process->getOutput()));
        }

        foreach ([
            [
                $newRelease,
                $priorRelease,
                'true',
                'false',
                '/tmp/untrusted-release',
                "{$releaseRoot}/{$priorRelease}",
                $releaseRoot,
            ],
            [
                $newRelease,
                $priorRelease,
                'true',
                'false',
                "{$releaseRoot}/{$newRelease}",
                '',
                $releaseRoot,
            ],
        ] as $arguments) {
            $process = $this->runActivationTargetClassifierHarness($arguments);

            $this->assertFalse($process->isSuccessful());
        }
    }

    public function test_workflow_classifies_activation_failure_before_restart_and_uses_one_recovery_decision(): void
    {
        $workflow = $this->read('.github/workflows/deploy-cloudron.yml');
        $activation = $this->position(
            $workflow,
            '- name: Extract and activate uploaded release',
        );
        $classification = $this->position(
            $workflow,
            '- name: Classify activation failure',
        );
        $restart = $this->position(
            $workflow,
            '- name: Restart activated release',
        );
        $recovery = $this->position(
            $workflow,
            '- name: Determine deployment recovery',
        );
        $rollback = $this->position(
            $workflow,
            '- name: Roll back code after failed release restart or verification',
        );

        $this->assertLessThan($classification, $activation);
        $this->assertLessThan($restart, $classification);
        $this->assertLessThan($rollback, $recovery);

        $activationBlock = substr($workflow, $activation, $classification - $activation);
        $classificationBlock = substr($workflow, $classification, $restart - $classification);
        $restartBlock = substr($workflow, $restart, $recovery - $restart);
        $recoveryBlock = substr($workflow, $recovery, $rollback - $recovery);

        $this->assertStringContainsString('id: activate', $activationBlock);
        $this->assertStringContainsString('continue-on-error: true', $activationBlock);
        $this->assertStringContainsString('id: activation_state', $classificationBlock);
        $this->assertStringContainsString(
            "if: always() && steps.activate.outcome == 'failure'",
            $classificationBlock,
        );
        $this->assertStringContainsString(
            '--classify-activation-state',
            $classificationBlock,
        );
        $this->assertStringContainsString(
            "if: steps.activate.outcome == 'success'",
            $restartBlock,
        );
        $this->assertStringContainsString('id: recovery', $recoveryBlock);
        $this->assertStringContainsString('failure_detected=', $recoveryBlock);
        $this->assertStringContainsString('rollback_required=', $recoveryBlock);
        $this->assertStringContainsString('failure_scope=', $recoveryBlock);

        $this->assertSame(3, substr_count(
            $workflow,
            "if: always() && steps.recovery.outputs.rollback_required == 'true'",
        ));
        $this->assertStringContainsString(
            "if: always() && steps.recovery.outputs.failure_detected == 'true'",
            $workflow,
        );
        $this->assertStringNotContainsString(
            "steps.previous.outputs.same_release != 'true' && (steps.restart.outcome == 'failure'",
            $workflow,
        );
    }

    public function test_deployment_staging_is_removed_only_after_rollback_verification_succeeds(): void
    {
        $workflow = $this->read('.github/workflows/deploy-cloudron.yml');
        $environmentGate = $this->position(
            $workflow,
            '- name: Verify restored release effective Laravel environment',
        );
        $healthGate = $this->position(
            $workflow,
            '- name: Verify restored release health and OIDC redirect',
        );
        $cleanup = $this->position(
            $workflow,
            '- name: Remove deployment staging after accepted outcome',
        );
        $failure = $this->position(
            $workflow,
            '- name: Fail deployment after unsuccessful outcome',
        );

        $environmentBlock = substr(
            $workflow,
            $environmentGate,
            $healthGate - $environmentGate,
        );
        $healthBlock = substr(
            $workflow,
            $healthGate,
            $cleanup - $healthGate,
        );
        $cleanupBlock = substr($workflow, $cleanup, $failure - $cleanup);

        $this->assertStringContainsString(
            'id: restored_environment',
            $environmentBlock,
        );
        $this->assertStringContainsString(
            'id: restored_verification',
            $healthBlock,
        );
        $this->assertStringContainsString(
            "steps.rollback.outcome == 'success'",
            $cleanupBlock,
        );
        $this->assertStringContainsString(
            "steps.restored_environment.outcome == 'success'",
            $cleanupBlock,
        );
        $this->assertStringContainsString(
            "steps.restored_verification.outcome == 'success'",
            $cleanupBlock,
        );
    }

    public function test_restored_release_verification_supports_the_v071_health_contract(): void
    {
        $workflow = $this->read('.github/workflows/deploy-cloudron.yml');
        $environmentGate = $this->position(
            $workflow,
            '- name: Verify restored release effective Laravel environment',
        );
        $healthGate = $this->position(
            $workflow,
            '- name: Verify restored release health and OIDC redirect',
        );
        $nextStep = $this->position(
            $workflow,
            '- name: Fail deployment after unsuccessful outcome',
        );

        $this->assertLessThan($healthGate, $environmentGate);
        $this->assertLessThan($nextStep, $healthGate);

        $environmentBlock = substr(
            $workflow,
            $environmentGate,
            $healthGate - $environmentGate,
        );
        $healthBlock = substr(
            $workflow,
            $healthGate,
            $nextStep - $healthGate,
        );

        $this->assertStringContainsString(
            'cloudron --server "$CLOUDRON_FQDN" --token "$CLOUDRON_TOKEN"',
            $environmentBlock,
        );
        $this->assertStringContainsString(
            '/app/data/current/artisan env --no-ansi',
            $environmentBlock,
        );
        $this->assertStringContainsString(
            'Restored release effective Laravel environment verification failed.',
            $environmentBlock,
        );
        $this->assertStringNotContainsString('APP_ENV=production', $environmentBlock);
        $this->assertStringContainsString(
            '"$reported_cache" == "ok"',
            $healthBlock,
        );
        $this->assertStringNotContainsString(
            'reported_environment',
            $healthBlock,
        );
    }

    public function test_deployment_controls_preserve_preexisting_operator_maintenance(): void
    {
        $activation = $this->read('scripts/deploy/activate-release.sh');
        $rollback = $this->read('scripts/deploy/rollback-release.sh');

        foreach ([$activation, $rollback] as $script) {
            $probe = $this->bashFunction($script, 'maintenance_state_for_release');
            $this->assertStringContainsString('Illuminate\\Contracts\\Console\\Kernel', $probe);
            $this->assertStringContainsString('maintenanceMode()->active()', $probe);
            $this->assertStringContainsString('active', $probe);
            $this->assertStringContainsString('inactive', $probe);
            $this->assertStringContainsString('PREEXISTING_MAINTENANCE', $script);
        }

        $this->assertLessThan(
            $this->position($activation, 'run_current_artisan down --retry=60'),
            $this->position($activation, 'maintenance_state_for_release "$CURRENT_TARGET_BEFORE"'),
        );
        $this->assertStringContainsString(
            'if [[ "$DEPLOYMENT_MAINTENANCE_ACTIVE" == true ]]; then',
            $activation,
        );
        $this->assertStringContainsString(
            'if [[ "$ROLLBACK_MAINTENANCE_ACTIVE" == true ]]; then',
            $rollback,
        );
    }

    public function test_new_release_health_requires_a_boolean_maintenance_field_without_requiring_false(): void
    {
        $workflow = $this->read('.github/workflows/deploy-cloudron.yml');
        $verification = substr(
            $workflow,
            $this->position($workflow, '- name: Verify exact release health and OIDC redirect'),
        );

        $this->assertStringContainsString(
            'reported_maintenance="$(jq -r',
            $verification,
        );
        $this->assertStringContainsString(
            '"$reported_maintenance" =~ ^(true|false)$',
            $verification,
        );
        $this->assertStringNotContainsString(
            '"$reported_maintenance" == "false"',
            $verification,
        );
    }

    public function test_rollback_health_accepts_boolean_or_legacy_absence_but_not_invalid_values(): void
    {
        $workflow = $this->read('.github/workflows/deploy-cloudron.yml');
        $verification = substr(
            $workflow,
            $this->position($workflow, '- name: Verify restored release health and OIDC redirect'),
            $this->position($workflow, '- name: Remove deployment staging after accepted outcome')
                - $this->position($workflow, '- name: Verify restored release health and OIDC redirect'),
        );

        $this->assertStringContainsString('if has("maintenance")', $verification);
        $this->assertStringContainsString('else "legacy" end', $verification);
        $this->assertStringContainsString(
            '"$reported_maintenance" =~ ^(true|false|legacy)$',
            $verification,
        );
    }

    public function test_maintenance_recovery_and_queue_contract_is_documented(): void
    {
        $readme = str_replace("\r\n", "\n", $this->read('README.md'));

        $this->assertStringContainsString('## Maintenance operations and recovery', $readme);
        $this->assertStringContainsString('APP_MAINTENANCE_DRIVER=cache', $readme);
        $this->assertStringContainsString('APP_MAINTENANCE_STORE=redis', $readme);
        $this->assertStringContainsString('without `--force`', $readme);
        $this->assertStringContainsString('queued Redis jobs remain durable', $readme);
        $this->assertStringContainsString('/admin/maintenance', $readme);
        $this->assertStringContainsString('/staff/auth/callback', $readme);
        $this->assertStringContainsString(
            "sudo -E -u www-data /usr/bin/php8.4 \\\n    /app/data/current/artisan up --no-interaction --no-ansi",
            $readme,
        );
        $this->assertStringContainsString(
            'exec --app "$CLOUDRON_APP_ID" -- \\',
            $readme,
        );
    }

    private function position(string $content, string $needle): int
    {
        $position = strpos($content, $needle);

        $this->assertNotFalse($position, "Expected deployment source to contain [{$needle}].");

        return $position;
    }

    private function lineContaining(string $content, string $needle): string
    {
        foreach (preg_split('/\R/', $content) ?: [] as $line) {
            if (str_contains($line, $needle)) {
                return $line;
            }
        }

        $this->fail("Expected deployment source to contain a line with [{$needle}].");
    }

    /**
     * @return list<string>
     */
    private function bashArray(string $script, string $name): array
    {
        $matched = preg_match(
            '/^'.preg_quote($name, '/').'=\(\R(?<items>.*?)^\)\R/ms',
            $script,
            $matches,
        );
        $this->assertSame(1, $matched, "Expected Bash array [{$name}].");

        return array_values(array_filter(array_map(
            static fn (string $line): string => trim($line, " \t\r\n\"'"),
            preg_split('/\R/', $matches['items']) ?: [],
        )));
    }

    private function deploymentScripts(): string
    {
        $scripts = glob($this->path('scripts/deploy/*.sh')) ?: [];
        $contents = array_map(
            static fn (string $script): string => (string) file_get_contents($script),
            $scripts,
        );

        return implode("\n", $contents);
    }

    /**
     * @param  list<string>  $controlPaths
     */
    private function writeControlManifest(
        string $root,
        string $manifestPath,
        array $controlPaths,
    ): void {
        $lines = [];
        foreach ($controlPaths as $relativePath) {
            $digest = hash_file(
                'sha256',
                $root.DIRECTORY_SEPARATOR.str_replace(
                    '/',
                    DIRECTORY_SEPARATOR,
                    $relativePath,
                ),
            );
            $this->assertIsString($digest);
            $lines[] = "{$digest}  {$relativePath}";
        }

        $this->assertNotFalse(file_put_contents(
            $manifestPath,
            implode("\n", $lines)."\n",
        ));
    }

    private function runControlVerifier(
        string $root,
        string $script,
        string $manifestHash,
    ): Process {
        return new Process([
            $this->bashExecutable(),
            $this->bashPath(
                $root.DIRECTORY_SEPARATOR.'scripts'.DIRECTORY_SEPARATOR.'deploy'
                    .DIRECTORY_SEPARATOR.$script,
            ),
            '--verify-controls',
            $this->bashPath($root),
            $manifestHash,
        ]);
    }

    /**
     * @return array{process: Process, commands: list<string>}
     */
    private function runActivationRecoveryHarness(
        string $mode,
        string $failCommand = '',
    ): array {
        $temporaryRoot = sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'myapes-activation-recovery-'.bin2hex(random_bytes(8));
        $this->assertTrue(mkdir($temporaryRoot, 0700, true));
        $script = $this->read('scripts/deploy/activate-release.sh');
        $harnessPath = $temporaryRoot.DIRECTORY_SEPARATOR.'harness.sh';
        $logPath = $temporaryRoot.DIRECTORY_SEPARATOR.'commands.log';
        $functions = $this->bashFunction($script, 'run_current_artisan')
            ."\n"
            .$this->bashFunction($script, 'run_artisan')
            ."\n"
            .$this->bashFunction(
                $script,
                'restore_current_authorization_after_failure',
            );
        $harness = <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail

MODE="$1"
LOG_PATH="$2"
FAIL_COMMAND="${3:-}"
CURRENT_TARGET_BEFORE="/release/current"
RELEASE_DIR="/release/new"
PHP_BIN="/usr/bin/php8.4"
PRE_SWITCH_DATABASE_MUTATED=true
PRE_SWITCH_MAINTENANCE_ACTIVE=true
ACTIVATION_SWITCHED=false

sudo() {
  [[ "${1:-}" == "-E" ]] && shift
  if [[ "${1:-}" == "-u" ]]; then
    shift 2
  fi
  [[ "${1:-}" == "env" ]] && shift
  [[ "${1:-}" == "APP_ENV=production" ]] && shift
  printf '%s\n' "$*" >>"$LOG_PATH"
  if [[ -n "$FAIL_COMMAND" && "$*" == *"$FAIL_COMMAND"* ]]; then
    return 1
  fi
}

__FUNCTIONS__

case "$MODE" in
  before-mutation) PRE_SWITCH_DATABASE_MUTATED=false ;;
  first-release) CURRENT_TARGET_BEFORE=""; PRE_SWITCH_MAINTENANCE_ACTIVE=false ;;
  same-release) CURRENT_TARGET_BEFORE="$RELEASE_DIR"; PRE_SWITCH_MAINTENANCE_ACTIVE=false ;;
  post-switch) ACTIVATION_SWITCHED=true ;;
  pre-switch-failure) ;;
  *) echo "Unknown harness mode."; exit 2 ;;
esac

trap restore_current_authorization_after_failure EXIT
false
BASH;
        $this->assertNotFalse(file_put_contents(
            $harnessPath,
            str_replace('__FUNCTIONS__', $functions, $harness),
        ));
        $process = new Process([
            $this->bashExecutable(),
            $this->bashPath($harnessPath),
            $mode,
            $this->bashPath($logPath),
            $failCommand,
        ]);

        try {
            $process->run();
            $commands = is_file($logPath)
                ? array_values(array_filter(preg_split(
                    '/\R/',
                    trim((string) file_get_contents($logPath)),
                ) ?: []))
                : [];
        } finally {
            $this->removeTemporaryDirectory($temporaryRoot);
        }

        return compact('process', 'commands');
    }

    /**
     * @param  list<string>  $arguments
     */
    private function runActivationTargetClassifierHarness(array $arguments): Process
    {
        $temporaryRoot = sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'myapes-activation-classifier-'.bin2hex(random_bytes(8));
        $this->assertTrue(mkdir($temporaryRoot, 0700, true));
        $harnessPath = $temporaryRoot.DIRECTORY_SEPARATOR.'harness.sh';
        $function = $this->bashFunction(
            $this->read('scripts/deploy/activate-release.sh'),
            'classify_activation_targets',
        );
        $this->assertNotFalse(file_put_contents(
            $harnessPath,
            "#!/usr/bin/env bash\nset -euo pipefail\n\n{$function}\nclassify_activation_targets \"\$@\"\n",
        ));
        $process = new Process([
            $this->bashExecutable(),
            $this->bashPath($harnessPath),
            ...$arguments,
        ]);

        try {
            $process->run();
        } finally {
            $this->removeTemporaryDirectory($temporaryRoot);
        }

        return $process;
    }

    private function bashFunction(string $script, string $name): string
    {
        $matched = preg_match(
            '/^'.preg_quote($name, '/').'\(\) \{\R.*?^\}\R/ms',
            $script,
            $matches,
        );
        $this->assertSame(1, $matched, "Expected Bash function [{$name}].");

        return $matches[0];
    }

    private function assertControlVerifierFails(
        string $root,
        string $script,
        string $manifestHash,
    ): void {
        $process = $this->runControlVerifier($root, $script, $manifestHash);
        $process->run();

        $this->assertFalse(
            $process->isSuccessful(),
            "Deployment control verifier unexpectedly succeeded:\n"
                .$process->getOutput().$process->getErrorOutput(),
        );
    }

    private function bashPath(string $path): string
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return $path;
        }

        $normalized = str_replace('\\', '/', $path);
        if (preg_match('/^(?<drive>[A-Za-z]):(?<rest>\/.*)$/', $normalized, $matches) === 1) {
            return '/'.strtolower($matches['drive']).$matches['rest'];
        }

        return $normalized;
    }

    private function bashExecutable(): string
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return 'bash';
        }

        $gitBash = 'C:\\Program Files\\Git\\bin\\bash.exe';
        $this->assertFileExists(
            $gitBash,
            'Git Bash is required for deployment-control verification tests.',
        );

        return $gitBash;
    }

    private function removeTemporaryDirectory(string $path): void
    {
        if (! is_dir($path)
            || (! str_starts_with(
                basename($path),
                'myapes-control-verifier-',
            ) && ! str_starts_with(
                basename($path),
                'myapes-activation-recovery-',
            ))) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $path,
                \FilesystemIterator::SKIP_DOTS,
            ),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($path);
    }

    private function read(string $relativePath): string
    {
        $content = file_get_contents($this->path($relativePath));

        $this->assertIsString($content, "Unable to read [{$relativePath}].");

        return $content;
    }

    private function path(string $relativePath): string
    {
        return dirname(__DIR__, 2).DIRECTORY_SEPARATOR.str_replace(
            '/',
            DIRECTORY_SEPARATOR,
            $relativePath,
        );
    }
}
