<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DeploymentAuthenticationContractTest extends TestCase
{
    public function test_authentication_gate_runs_before_migrations_caches_and_atomic_activation(): void
    {
        $script = $this->read('scripts/deploy/activate-release.sh');

        $optimize = $this->position($script, 'optimize:clear');
        $authCheck = $this->position($script, 'myapes:auth-check');
        $migration = $this->position($script, 'migrate --force');
        $configCache = $this->position($script, 'config:cache');
        $routeCache = $this->position($script, 'route:cache');
        $viewCache = $this->position($script, 'view:cache');
        $atomicSwitch = $this->position($script, 'mv -Tf "${CURRENT_LINK}.next" "$CURRENT_LINK"');

        $this->assertLessThan($authCheck, $optimize);
        $this->assertLessThan($migration, $authCheck);
        $this->assertLessThan($configCache, $migration);
        $this->assertLessThan($routeCache, $configCache);
        $this->assertLessThan($viewCache, $routeCache);
        $this->assertLessThan($atomicSwitch, $viewCache);

        $authLine = $this->lineContaining($script, 'myapes:auth-check');
        $this->assertStringContainsString('sudo -E -u www-data', $authLine);
        $this->assertStringContainsString('--no-interaction', $authLine);
        $this->assertStringContainsString('--no-ansi', $authLine);
    }

    public function test_workflow_combines_health_and_oidc_smoke_verification_and_rolls_back_on_failure(): void
    {
        $workflow = $this->read('.github/workflows/deploy-cloudron.yml');
        $deploymentSources = $workflow."\n".$this->deploymentScripts();

        $this->assertMatchesRegularExpression(
            '/\bid:\s*verify\s*\R(?:(?!\bid:).){0,300}?continue-on-error:\s*true/s',
            $workflow,
        );
        $this->assertMatchesRegularExpression(
            '/\bif:\s*(?:\$\{\{\s*)?steps\.verify\.outcome\s*==\s*[\'"]failure[\'"](?:\s*\}\})?/',
            $workflow,
        );

        $this->assertStringContainsString('/healthz', $workflow);
        $this->assertStringContainsString('reported_version', $workflow);
        $this->assertStringContainsString('APP_VERSION', $workflow);
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
            '/deploy:\s*\R(?:(?!\n\S).)*if:\s*\$\{\{\s*github\.event_name\s*!=\s*[\'"]pull_request[\'"]\s*\}\}/s',
            $workflow,
        );
    }

    public function test_workflow_packages_and_verifies_semantic_and_commit_identities(): void
    {
        $workflow = $this->read('.github/workflows/deploy-cloudron.yml');

        $this->assertStringContainsString('app_version:', $workflow);
        $this->assertStringContainsString('app_version=', $workflow);
        $this->assertStringContainsString("grep -qx './VERSION' build/archive-list.txt", $workflow);
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
    }

    public function test_release_exclusion_only_rejects_the_git_directory(): void
    {
        $workflow = $this->read('.github/workflows/deploy-cloudron.yml');

        $this->assertStringContainsString('\.git($|/)', $workflow);
        $this->assertStringNotContainsString('^\./(\.git|', $workflow);
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

    private function deploymentScripts(): string
    {
        $scripts = glob($this->path('scripts/deploy/*.sh')) ?: [];
        $contents = array_map(
            static fn (string $script): string => (string) file_get_contents($script),
            $scripts,
        );

        return implode("\n", $contents);
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
