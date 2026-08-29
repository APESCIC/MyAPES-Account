<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CloudGhActionsContractTest extends TestCase
{
    public function test_cloud_scripts_exist_and_are_executable(): void
    {
        foreach ([
            'scripts/cloud/gh-actions.sh',
            'scripts/cloud/configure-gh-auth.sh',
            'scripts/cloud/install.sh',
        ] as $path) {
            $this->assertFileExists($path);
            $this->assertTrue(is_executable($path), "Expected {$path} to be executable.");
        }
    }

    public function test_gh_actions_script_uses_pat_for_write_operations(): void
    {
        $script = $this->read('scripts/cloud/gh-actions.sh');

        $this->assertStringContainsString('GH_ACTIONS_TOKEN', $script);
        $this->assertStringContainsString('GH_TOKEN="$GH_ACTIONS_TOKEN"', $script);
        $this->assertStringContainsString('workflow run', $script);
        $this->assertStringContainsString('run rerun', $script);
        $this->assertStringContainsString('workflow-run', $script);
        $this->assertStringContainsString('run-rerun', $script);
        $this->assertStringContainsString('run-watch', $script);
    }

    public function test_configure_gh_auth_verifies_pat_without_replacing_default_login(): void
    {
        $script = $this->read('scripts/cloud/configure-gh-auth.sh');

        $this->assertStringContainsString('GH_ACTIONS_TOKEN', $script);
        $this->assertStringContainsString('GH_TOKEN="$GH_ACTIONS_TOKEN"', $script);
        $this->assertStringNotContainsString('gh auth login', $script);
    }

    public function test_environment_json_wires_cloud_scripts(): void
    {
        $environment = json_decode($this->read('.cursor/environment.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('Dockerfile', $environment['build']['dockerfile']);
        $this->assertSame('..', $environment['build']['context']);
        $this->assertSame('bash scripts/cloud/install.sh', $environment['install']);
        $this->assertSame('bash scripts/cloud/configure-gh-auth.sh', $environment['start']);
        $this->assertSame('composer run dev', $environment['termininals'][0]['command']);
    }

    public function test_dockerfile_includes_php_node_and_composer(): void
    {
        $dockerfile = $this->read('.cursor/Dockerfile');

        $this->assertStringContainsString('php8.4-cli', $dockerfile);
        $this->assertStringContainsString('nodejs', $dockerfile);
        $this->assertStringContainsString('composer', $dockerfile);
    }

    public function test_ship_gate_skill_uses_cloud_actions_helper(): void
    {
        $skill = $this->read('.cursor/skills/ship-gate/SKILL.md');

        $this->assertStringContainsString('scripts/cloud/gh-actions.sh workflow-run', $skill);
        $this->assertStringContainsString('scripts/cloud/gh-actions.sh run-watch', $skill);
        $this->assertStringContainsString('scripts/cloud/gh-actions.sh run-rerun', $skill);
        $this->assertStringNotContainsString('gh workflow run "Deploy MyAPES Core to Cloudron"', $skill);
    }

    public function test_agents_md_documents_cloud_actions_requirements(): void
    {
        $agents = $this->read('AGENTS.md');

        $this->assertStringContainsString('## Cloud Agents', $agents);
        $this->assertStringContainsString('GH_ACTIONS_TOKEN', $agents);
        $this->assertStringContainsString('scripts/cloud/gh-actions.sh', $agents);
    }

    private function read(string $path): string
    {
        $contents = file_get_contents($path);

        $this->assertNotFalse($contents, "Unable to read {$path}.");

        return $contents;
    }
}
