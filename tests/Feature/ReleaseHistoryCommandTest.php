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

        $this->assertSame('0.25.7', $repository->version());
        $this->assertSame('0.25.7', $repository->current()['version']);
        $this->assertSame(
            ['0.25.7', '0.25.6', '0.25.5', '0.25.4', '0.25.3', '0.25.2', '0.25.1', '0.25.0', '0.24.1', '0.24.0', '0.23.1', '0.23.0', '0.22.0', '0.21.1', '0.21.0', '0.20.0', '0.19.4', '0.19.3', '0.19.2', '0.19.1', '0.19.0', '0.18.2', '0.18.1', '0.18.0', '0.17.0', '0.16.3', '0.16.1', '0.16.0', '0.15.0', '0.14.0', '0.13.1', '0.13.0', '0.12.1', '0.12.0', '0.11.0', '0.10.0', '0.9.2', '0.9.1', '0.9.0', '0.8.3', '0.8.2', '0.8.1', '0.8.0', '0.7.1', '0.7.0', '0.6.1', '0.6.0', '0.5.0', '0.4.2', '0.4.1', '0.4.0', '0.3.0', '0.2.1', '0.2.0', '0.1.0'],
            array_column($releases, 'version'),
        );
        $this->assertSame('2026-08-25', $releases[0]['date']);
        $this->assertSame('2026-08-25', $releases[1]['date']);
        $this->assertSame('2026-08-25', $releases[2]['date']);
        $this->assertSame('2026-08-25', $releases[3]['date']);
        $this->assertSame('2026-08-24', $releases[4]['date']);
        $this->assertSame('2026-08-24', $releases[5]['date']);
        $this->assertSame('2026-08-24', $releases[6]['date']);
        $this->assertSame('2026-08-24', $releases[7]['date']);
        $this->assertSame('2026-08-24', $releases[8]['date']);
        $this->assertSame('2026-08-24', $releases[9]['date']);
        $this->assertSame('2026-08-23', $releases[10]['date']);
        $this->assertSame('2026-08-22', $releases[11]['date']);
        $this->assertSame('2026-08-22', $releases[12]['date']);
        $this->assertSame('2026-08-22', $releases[13]['date']);
        $this->assertSame('2026-08-20', $releases[14]['date']);
        $this->assertSame('2026-08-20', $releases[15]['date']);
        $this->assertSame('2026-08-20', $releases[16]['date']);
        $this->assertSame('2026-08-20', $releases[17]['date']);
        $this->assertSame('2026-08-20', $releases[18]['date']);
        $this->assertSame('2026-08-20', $releases[19]['date']);
        $this->assertSame('2026-08-20', $releases[20]['date']);
        $this->assertSame('2026-08-20', $releases[21]['date']);
        $this->assertSame('2026-08-20', $releases[22]['date']);
        $this->assertSame('2026-08-20', $releases[23]['date']);
        $this->assertSame('2026-08-19', $releases[24]['date']);
        $this->assertSame('2026-08-19', $releases[25]['date']);
        $this->assertSame('2026-08-19', $releases[26]['date']);
        $this->assertSame('2026-08-19', $releases[27]['date']);
        $this->assertSame('2026-08-14', $releases[28]['date']);
        $this->assertSame('2026-08-14', $releases[29]['date']);
        $this->assertSame('2026-08-11', $releases[30]['date']);
        $this->assertSame('2026-08-10', $releases[31]['date']);
        $this->assertSame('2026-08-10', $releases[32]['date']);
        $this->assertSame('2026-08-10', $releases[33]['date']);
        $this->assertSame('2026-08-09', $releases[34]['date']);
        $this->assertSame('2026-08-09', $releases[35]['date']);
        $this->assertSame('2026-08-09', $releases[36]['date']);
        $this->assertSame('2026-08-08', $releases[37]['date']);
        $this->assertSame('2026-08-07', $releases[38]['date']);
        $this->assertSame('2026-08-06', $releases[39]['date']);
        $this->assertSame('2026-08-05', $releases[40]['date']);
        $this->assertSame('2026-08-05', $releases[41]['date']);
        $this->assertSame('2026-08-05', $releases[42]['date']);
        $this->assertSame('2026-07-28', $releases[43]['date']);
        $this->assertSame('2026-07-27', $releases[44]['date']);
        $this->assertSame('2026-07-27', $releases[45]['date']);
        $this->assertSame('2026-07-27', $releases[46]['date']);
        $this->assertSame('2026-07-27', $releases[47]['date']);

        foreach (array_slice($releases, 48) as $release) {
            $this->assertSame('2026-07-24', $release['date']);
        }

        foreach (array_slice($releases, 47) as $release) {
            $this->assertStringContainsString('reconstructed from merged pull request', $release['provenance']);
        }

        $current = $repository->current();
        $this->assertSame('stable', $current['channel']);
        $this->assertSame('patch', $current['type']);
        $this->assertSame(
            [
                [
                    'label' => 'Issue #93',
                    'url' => 'https://github.com/APESCIC/MyAPES-Account/issues/93',
                ],
                [
                    'label' => 'Pull request #116',
                    'url' => 'https://github.com/APESCIC/MyAPES-Account/pull/116',
                ],
            ],
            $current['references'],
        );
        $currentText = strtolower(json_encode($current, JSON_THROW_ON_ERROR));
        foreach ([
            'volunteer',
            'student',
            'delete',
            'issue #93',
        ] as $requiredReleaseText) {
            $this->assertStringContainsString($requiredReleaseText, $currentText);
        }

        $desertRelease = $releases[27];
        $this->assertSame('minor', $desertRelease['type']);
        $desertText = strtolower(json_encode($desertRelease, JSON_THROW_ON_ERROR));
        foreach ([
            'desert',
            'spike',
            'bearded dragon',
            'cartoon',
            'dismissible',
            'no database migration',
            'local browser preference',
            'issue #47',
        ] as $requiredReleaseText) {
            $this->assertStringContainsString($requiredReleaseText, $desertText);
        }
        $this->assertStringNotContainsString('pull request', $desertText);

        $apesCicRelease = $releases[31];
        $this->assertSame('minor', $apesCicRelease['type']);
        $this->assertSame(
            [[
                'label' => 'Issue #12',
                'url' => 'https://github.com/APESCIC/MyAPES-Account/issues/12',
            ]],
            $apesCicRelease['references'],
        );
        $this->assertStringContainsString(
            'database recovery',
            strtolower(implode(' ', $apesCicRelease['known_limitations'])),
        );

        $phaseB = $releases[42];
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
            ->expectsOutputToContain('Release history is valid at v0.25.7')
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
