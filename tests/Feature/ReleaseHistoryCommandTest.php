<?php

namespace Tests\Feature;

use App\Support\ReleaseHistoryRepository;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ReleaseHistoryCommandTest extends TestCase
{
    public function test_source_controlled_history_contains_the_required_release_sequence(): void
    {
        if (! class_exists(ReleaseHistoryRepository::class)) {
            $this->fail('ReleaseHistoryRepository has not been implemented.');
        }

        $repository = app(ReleaseHistoryRepository::class);
        $releases = $repository->all();

        $this->assertSame('0.7.1', $repository->version());
        $this->assertSame('0.7.1', $repository->current()['version']);
        $this->assertSame(
            ['0.7.1', '0.7.0', '0.6.1', '0.6.0', '0.5.0', '0.4.2', '0.4.1', '0.4.0', '0.3.0', '0.2.1', '0.2.0', '0.1.0'],
            array_column($releases, 'version'),
        );
        $this->assertSame('2026-07-28', $releases[0]['date']);
        $this->assertSame('2026-07-27', $releases[1]['date']);
        $this->assertSame('2026-07-27', $releases[2]['date']);
        $this->assertSame('2026-07-27', $releases[3]['date']);
        $this->assertSame('2026-07-27', $releases[4]['date']);

        foreach (array_slice($releases, 5) as $release) {
            $this->assertSame('2026-07-24', $release['date']);
        }

        foreach (array_slice($releases, 4) as $release) {
            $this->assertStringContainsString('reconstructed from merged pull request', $release['provenance']);
        }
    }

    public function test_validation_command_accepts_the_bootstrap_history(): void
    {
        $this->artisan('myapes:changelog-validate')
            ->expectsOutputToContain('Release history is valid at v0.7.1')
            ->assertSuccessful();
    }

    public function test_validation_command_treats_a_base_without_release_files_as_bootstrap(): void
    {
        $rootCommit = new Process(
            ['git', 'rev-list', '--max-parents=0', 'HEAD'],
            base_path(),
        );
        $rootCommit->mustRun();

        $this->artisan('myapes:changelog-validate', ['--base-ref' => trim($rootCommit->getOutput())])
            ->expectsOutputToContain('Base release contract is absent; structural validation only.')
            ->assertSuccessful();
    }

    public function test_validation_command_rejects_an_unknown_base_ref(): void
    {
        $this->artisan('myapes:changelog-validate', ['--base-ref' => 'refs/heads/not-a-real-release-base'])
            ->expectsOutputToContain('Unable to resolve base ref')
            ->assertFailed();
    }
}
