<?php

namespace App\Console\Commands;

use App\Support\ReleaseHistoryPreparer;
use Illuminate\Console\Command;
use Throwable;

class PrepareChangeLog extends Command
{
    protected $signature = 'myapes:changelog-prepare
        {--type=patch : Semantic version bump type: major, minor, or patch}
        {--title= : Short public release title}
        {--issue= : GitHub issue number for release references}
        {--pr= : GitHub pull request number for release references (required)}
        {--channel=stable : Release channel}
        {--date= : ISO release date (YYYY-MM-DD); defaults to today}
        {--dry-run : Print the planned release scaffold without writing files}';

    protected $description = 'Scaffold the next release record and sync VERSION, runtime manifest, and version-pinned tests';

    public function handle(ReleaseHistoryPreparer $preparer): int
    {
        $title = trim((string) $this->option('title'));
        $type = strtolower(trim((string) $this->option('type')));
        $channel = strtolower(trim((string) $this->option('channel')));
        $date = trim((string) $this->option('date'));
        $dryRun = (bool) $this->option('dry-run');

        if ($date === '') {
            $date = now()->format('Y-m-d');
        }

        $pullRequestNumber = $this->parseOptionalPositiveInteger('pr');
        if ($pullRequestNumber === null) {
            $this->components->error('The --pr option is required so every release cites its merging pull request.');

            return self::FAILURE;
        }

        try {
            $issueNumber = $this->parseOptionalPositiveInteger('issue');
            if ($dryRun) {
                $plan = $preparer->plan($type, $title, $channel, $date, $issueNumber, $pullRequestNumber);
            } else {
                $plan = $preparer->apply($type, $title, $channel, $date, $issueNumber, $pullRequestNumber);
            }
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info(
            ($dryRun ? 'Dry run: would prepare' : 'Prepared')
            ." release v{$plan['next_version']} (from v{$plan['previous_version']}).",
        );

        $this->line('Files:');
        foreach ($plan['files'] as $file) {
            $this->line("  - {$file}");
        }

        $this->newLine();
        $this->line(json_encode($plan['stub_record'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if ($dryRun) {
            $this->newLine();
            $this->components->warn('Dry run only; no files were modified.');
        } else {
            $this->newLine();
            $this->components->warn('Replace every TODO: field in the new releases.json head record.');
            $this->components->warn('Then run: git fetch origin && php artisan myapes:changelog-validate --base-ref=origin/main');
        }

        return self::SUCCESS;
    }

    private function parseOptionalPositiveInteger(string $option): ?int
    {
        $value = trim((string) $this->option($option));

        if ($value === '') {
            return null;
        }

        if (! ctype_digit($value) || (int) $value < 1) {
            throw new \InvalidArgumentException("The --{$option} option must be a positive integer.");
        }

        return (int) $value;
    }
}
