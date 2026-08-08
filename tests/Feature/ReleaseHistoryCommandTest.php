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

        $this->assertSame('0.10.0', $repository->version());
        $this->assertSame('0.10.0', $repository->current()['version']);
        $this->assertSame(
            ['0.10.0', '0.9.2', '0.9.1', '0.9.0', '0.8.3', '0.8.2', '0.8.1', '0.8.0', '0.7.1', '0.7.0', '0.6.1', '0.6.0', '0.5.0', '0.4.2', '0.4.1', '0.4.0', '0.3.0', '0.2.1', '0.2.0', '0.1.0'],
            array_column($releases, 'version'),
        );
        $this->assertSame('2026-08-09', $releases[0]['date']);
        $this->assertSame('2026-08-09', $releases[1]['date']);
        $this->assertSame('2026-08-08', $releases[2]['date']);
        $this->assertSame('2026-08-07', $releases[3]['date']);
        $this->assertSame('2026-08-06', $releases[4]['date']);
        $this->assertSame('2026-08-05', $releases[5]['date']);
        $this->assertSame('2026-08-05', $releases[6]['date']);
        $this->assertSame('2026-08-05', $releases[7]['date']);
        $this->assertSame('2026-07-28', $releases[8]['date']);
        $this->assertSame('2026-07-27', $releases[9]['date']);
        $this->assertSame('2026-07-27', $releases[10]['date']);
        $this->assertSame('2026-07-27', $releases[11]['date']);
        $this->assertSame('2026-07-27', $releases[12]['date']);

        foreach (array_slice($releases, 13) as $release) {
            $this->assertSame('2026-07-24', $release['date']);
        }

        foreach (array_slice($releases, 12) as $release) {
            $this->assertStringContainsString('reconstructed from merged pull request', $release['provenance']);
        }

        $current = $repository->current();
        $this->assertSame('stable', $current['channel']);
        $this->assertSame('minor', $current['type']);
        $this->assertSame(
            [[
                'label' => 'Issue #19',
                'url' => 'https://github.com/APESCIC/MyAPES-Account/issues/19',
            ]],
            $current['references'],
        );
        $this->assertStringContainsString(
            'gdpr',
            strtolower(implode(' ', $current['known_limitations'])),
        );

        $phaseB = $releases[7];
        $this->assertStringContainsString(
            'issue #11',
            strtolower(implode(' ', $phaseB['known_limitations'])),
        );
        $this->assertStringContainsString(
            'issue #19',
            strtolower(implode(' ', $phaseB['known_limitations'])),
        );
        $this->assertStringNotContainsString(
            'pull request',
            strtolower(json_encode($phaseB, JSON_THROW_ON_ERROR)),
        );
    }

    public function test_validation_command_accepts_the_bootstrap_history(): void
    {
        $this->artisan('myapes:changelog-validate')
            ->expectsOutputToContain('Release history is valid at v0.10.0')
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
