<?php

namespace App\Console\Commands;

use App\Services\Localisation\TranslationKeyChecker;
use Illuminate\Console\Command;

/**
 * Translation key check for Language #276 — missing keys hard-fail when requested.
 */
class LangCheckCommand extends Command
{
    protected $signature = 'lang:check
                            {--fail-on-missing : Exit non-zero when missing keys are found}
                            {--json : Print machine JSON to stdout}';

    protected $description = 'Report missing/unused translation keys; optionally fail on missing (#276)';

    public function handle(TranslationKeyChecker $checker): int
    {
        $report = $checker->check();

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}');
        } else {
            $this->info('Translation key check (#276)');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Used literal keys', count($report['used_keys'])],
                    ['Missing keys', count($report['missing_keys'])],
                    ['Unused keys (allow-listed omitted)', count($report['unused_keys'])],
                    ['Defined en_GB keys', count($report['defined_keys'])],
                    ['Hard-coded inventory findings', $report['hard_coded_count']],
                ],
            );

            if ($report['missing_keys'] !== []) {
                $this->warn('Missing keys:');
                foreach (array_slice($report['missing_keys'], 0, 50) as $missing) {
                    $this->line("  {$missing['file']}:{$missing['line']} → {$missing['key']}");
                }
                if (count($report['missing_keys']) > 50) {
                    $this->line('  … and '.(count($report['missing_keys']) - 50).' more');
                }
            }

            if ($report['unused_keys'] !== []) {
                $this->comment('Unused keys (non-blocking): '.implode(', ', array_slice($report['unused_keys'], 0, 30)));
            }
        }

        if ($this->option('fail-on-missing') && $report['missing_keys'] !== []) {
            $this->error('Failing because --fail-on-missing is set and missing keys were found.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
