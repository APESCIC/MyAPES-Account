<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class DeploymentAuthenticationContractTest extends TestCase
{
    public function test_phase_b_activation_runs_once_in_the_fail_closed_order_as_www_data(): void
    {
        $script = $this->read('scripts/deploy/activate-release.sh');

        $orderedCommands = [
            'optimize:clear',
            'myapes:authorization-preflight --no-interaction --no-ansi',
            'migrate --force',
            'storage:link --force',
            'config:cache',
            'route:cache',
            'view:cache',
            'permission:cache-reset --no-interaction',
            'myapes:directory-sync --source=manual --no-interaction --no-ansi',
            'myapes:authorization-sync --no-interaction --no-ansi',
            'permission:cache-reset --no-interaction',
            'myapes:authorization-check --no-interaction --no-ansi',
            'mv -Tf "${CURRENT_LINK}.next" "$CURRENT_LINK"',
        ];
        $offset = 0;

        foreach ($orderedCommands as $command) {
            $position = strpos($script, $command, $offset);
            $this->assertNotFalse(
                $position,
                "Expected activation to contain [{$command}] after offset {$offset}.",
            );
            $offset = $position + strlen($command);
        }

        foreach (array_slice($orderedCommands, 0, -1) as $command) {
            $line = $this->lineContaining($script, $command);
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
        $this->assertSame(2, substr_count($script, 'permission:cache-reset'));
        $this->assertSame(1, substr_count($script, 'myapes:authorization-preflight'));
        $this->assertSame(1, substr_count($script, 'myapes:directory-sync'));
        $this->assertSame(1, substr_count($script, 'myapes:authorization-sync'));
        $this->assertSame(1, substr_count($script, 'myapes:authorization-check'));
        $this->assertStringNotContainsString('myapes:auth-check', $script);
        $this->assertStringNotContainsString('myapes:access-compatibility-sync', $script);
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
            'config/permission.php',
            'database/migrations/2026_07_28_000000_create_permission_tables.php',
            'database/migrations/2026_07_28_000100_cut_over_authorization_domain.php',
            'app/Console/Commands/AuthorizationPreflight.php',
            'app/Console/Commands/DirectorySync.php',
            'app/Console/Commands/AuthorizationSync.php',
            'app/Console/Commands/AuthorizationCheck.php',
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
        $verify = $this->position($workflow, '- name: Verify exact release health and OIDC redirect');
        $rollback = $this->position($workflow, '- name: Roll back code after failed release restart or verification');

        $this->assertLessThan($restart, $activation);
        $this->assertLessThan($verify, $restart);
        $this->assertLessThan($rollback, $verify);
        $this->assertMatchesRegularExpression(
            '/name:\s*Restart activated release\R\s+id:\s*restart\R\s+continue-on-error:\s*true/s',
            $workflow,
        );
        $this->assertMatchesRegularExpression(
            '/\bid:\s*verify\s*\R(?:(?!\bid:).){0,300}?continue-on-error:\s*true/s',
            $workflow,
        );
        $this->assertMatchesRegularExpression(
            '/\bif:\s*(?:\$\{\{\s*)?steps\.restart\.outcome\s*==\s*[\'"]success[\'"](?:\s*\}\})?/',
            $workflow,
        );
        $this->assertMatchesRegularExpression(
            '/\bif:\s*(?:\$\{\{\s*)?always\(\)\s*&&\s*steps\.previous\.outputs\.same_release\s*!=\s*[\'"]true[\'"]\s*&&\s*\(steps\.restart\.outcome\s*==\s*[\'"]failure[\'"]\s*\|\|\s*steps\.verify\.outcome\s*==\s*[\'"]failure[\'"]\)(?:\s*\}\})?/',
            $workflow,
        );
        $this->assertStringContainsString(
            "steps.previous.outputs.same_release == 'true'",
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
            'bash "/app/data/deployment-controls/$failed_release/scripts/deploy/rollback-release.sh"',
            $workflow,
        );
        $this->assertStringContainsString(
            '"$previous_release" "$failed_release" "$expected_controls_sha256"',
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

        $this->assertMatchesRegularExpression('/pull_request:\s*\R/', $workflow);
        $this->assertStringContainsString('fetch-depth: 0', $workflow);
        $this->assertStringContainsString('github.event.pull_request.base.sha', $workflow);
        $this->assertStringContainsString('github.event.before', $workflow);
        $this->assertStringContainsString('myapes:changelog-validate', $workflow);
        $this->assertStringContainsString('npm run test:frontend', $workflow);
        $this->assertMatchesRegularExpression(
            '/deploy:\s*\R(?:(?!\n\S).)*if:\s*\$\{\{\s*github\.event_name\s*==\s*[\'"]push[\'"]\s*&&\s*github\.ref\s*==\s*[\'"]refs\/heads\/main[\'"]\s*\}\}/s',
            $workflow,
        );
    }

    public function test_workflow_runs_the_phase_b_contract_on_mysql_and_mariadb(): void
    {
        $workflow = $this->read('.github/workflows/deploy-cloudron.yml');

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
            $workflow,
        );
    }

    public function test_workflow_packages_and_verifies_semantic_and_commit_identities(): void
    {
        $workflow = $this->read('.github/workflows/deploy-cloudron.yml');

        $this->assertStringContainsString('app_version:', $workflow);
        $this->assertStringContainsString('app_version=', $workflow);
        $this->assertStringContainsString("grep -qx './VERSION' build/archive-list.txt", $workflow);
        foreach ([
            'resources/data/releases.json',
            'config/permission.php',
            'database/migrations/2026_07_28_000000_create_permission_tables.php',
            'database/migrations/2026_07_28_000100_cut_over_authorization_domain.php',
            'app/Console/Commands/AuthorizationPreflight.php',
            'app/Console/Commands/DirectorySync.php',
            'app/Console/Commands/AuthorizationSync.php',
            'app/Console/Commands/AuthorizationCheck.php',
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
                'bash "/app/data/deployment-controls/$failed_release/scripts/deploy/rollback-release.sh"',
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
            'CONTROL_RELEASES_DIR="${DATA_DIR}/deployment-controls"',
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
            'CONTROL_ROOT="${CONTROL_RELEASES_DIR}/${EXPECTED_CURRENT_SHA}"',
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
            'bash "/app/data/deployment-controls/$failed_release/scripts/deploy/rollback-release.sh"',
            $workflow,
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

    public function test_code_rollback_validates_the_previous_release_and_never_reverses_migrations(): void
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
            'install -m 0644 "${CONTROL_ROOT}/scripts/deploy/cloudron-app.conf"',
            $script,
        );
        $this->assertStringContainsString(
            'install -m 0755 "${CONTROL_ROOT}/scripts/deploy/cloudron-run.sh"',
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
            'install -m 0644 "${CONTROL_ROOT}/scripts/deploy/cloudron-app.conf"',
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
        $this->assertStringContainsString('ln -s "$ROLLBACK_TARGET" "${CURRENT_LINK}.rollback"', $script);
        $this->assertStringContainsString('mv -Tf "${CURRENT_LINK}.rollback" "$CURRENT_LINK"', $script);
        $this->assertStringContainsString('scripts/deploy/cloudron-app.conf', $script);
        $this->assertStringContainsString('scripts/deploy/cloudron-run.sh', $script);
        $this->assertStringContainsString('Database migrations were retained', $script);
        $this->assertStringNotContainsString('migrate:rollback', $script);
        $this->assertStringNotContainsString('migrate:reset', $script);
        $this->assertStringNotContainsString('migrate:fresh', $script);
        $this->assertDoesNotMatchRegularExpression('/\bdown\b/', $script);
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
            '- name: Fail an idempotent deployment after restart or verification failure',
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
            || ! str_starts_with(
                basename($path),
                'myapes-control-verifier-',
            )) {
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
