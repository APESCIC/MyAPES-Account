<?php

namespace App\Console\Commands;

use App\Services\Localisation\HardCodedStringInventoryReporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LangInventoryCommand extends Command
{
    protected $signature = 'lang:inventory
                            {--write : Write docs/i18n-inventory.md and storage/app/lang-inventory.json}
                            {--json : Print machine JSON to stdout instead of a table summary}';

    protected $description = 'Inventory hard-coded user-facing strings in Blade and PHP (Language #266; reusable by #276)';

    public function handle(HardCodedStringInventoryReporter $reporter): int
    {
        $payload = $reporter->build();

        if ($this->option('json') && ! $this->option('write')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}');

            return self::SUCCESS;
        }

        if ($this->option('write')) {
            $markdownRelative = (string) config('lang_inventory.markdown_path', 'docs/i18n-inventory.md');
            $jsonRelative = (string) config('lang_inventory.json_path', 'storage/app/lang-inventory.json');

            $markdownPath = base_path($markdownRelative);
            $jsonPath = base_path($jsonRelative);

            File::ensureDirectoryExists(dirname($markdownPath));
            File::ensureDirectoryExists(dirname($jsonPath));

            File::put($markdownPath, $reporter->toMarkdown($payload));
            File::put(
                $jsonPath,
                json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n",
            );

            $this->info("Wrote {$markdownRelative}");
            $this->info("Wrote {$jsonRelative}");
        }

        $this->table(
            ['Area', 'Count'],
            collect($payload['totals'])
                ->map(fn ($count, $area) => [$area, $count])
                ->values()
                ->all(),
        );

        $this->line('Allow-listed: '.$payload['allowlisted_count']);

        return self::SUCCESS;
    }
}
