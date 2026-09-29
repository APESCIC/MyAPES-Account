<?php

namespace App\Services\Localisation;

/**
 * Formats hard-coded string scan results for docs + machine JSON (#266 / #276).
 */
class HardCodedStringInventoryReporter
{
    public function __construct(
        private readonly HardCodedStringScanner $scanner = new HardCodedStringScanner,
    ) {}

    /**
     * @return array{
     *     generated_at: string,
     *     totals: array<string, int>,
     *     allowlisted_count: int,
     *     findings: list<array<string, mixed>>,
     *     allowlisted: list<array<string, mixed>>
     * }
     */
    public function build(?string $basePath = null): array
    {
        $all = $this->scanner->scan($basePath);
        $inventory = [];
        $allowlisted = [];
        $totals = [];

        foreach (array_keys(config('lang_inventory.area_labels', [])) as $area) {
            $totals[$area] = 0;
        }

        foreach ($all as $finding) {
            if ($finding->allowlisted) {
                $allowlisted[] = $finding->toArray();

                continue;
            }

            $inventory[] = $finding->toArray();
            $totals[$finding->area] = ($totals[$finding->area] ?? 0) + 1;
        }

        $totals['all'] = count($inventory);

        return [
            'generated_at' => now()->toIso8601String(),
            'totals' => $totals,
            'allowlisted_count' => count($allowlisted),
            'findings' => $inventory,
            'allowlisted' => $allowlisted,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function toMarkdown(array $payload): string
    {
        $labels = config('lang_inventory.area_labels', []);
        $anchors = config('lang_inventory.area_anchors', []);
        $issues = config('lang_inventory.extraction_issues', []);

        $lines = [];
        $lines[] = '# Hard-coded UI string inventory';
        $lines[] = '';
        $lines[] = 'Generated for [#266](https://github.com/APESCIC/MyAPES-Account/issues/266). This is a checklist for Language extraction children — **do not** treat it as a mandate to extract everything in one PR.';
        $lines[] = '';
        $lines[] = 'Regenerate:';
        $lines[] = '';
        $lines[] = '```bash';
        $lines[] = 'php artisan lang:inventory --write';
        $lines[] = '```';
        $lines[] = '';
        $lines[] = 'Scanner: `App\\Services\\Localisation\\HardCodedStringScanner` (reusable by [#276](https://github.com/APESCIC/MyAPES-Account/issues/276)).';
        $lines[] = 'Allow-list: `config/lang_inventory.php` → `allowlist`.';
        $lines[] = 'Machine JSON (gitignored under storage): `storage/app/lang-inventory.json`.';
        $lines[] = '';
        $lines[] = '## Section index (extraction children)';
        $lines[] = '';
        $lines[] = '| Extraction issue | Inventory section | Anchor |';
        $lines[] = '| --- | --- | --- |';
        $lines[] = '| [#268](https://github.com/APESCIC/MyAPES-Account/issues/268) Public | [Public](#'.$anchors['public'].') + [Plugin: Recruitment](#'.$anchors['plugin_recruitment'].') (public views) + [Plugin: Pet Profiles](#'.$anchors['plugin_pet_profiles'].') | `docs/i18n-inventory.md#'.$anchors['public'].'` |';
        $lines[] = '| [#269](https://github.com/APESCIC/MyAPES-Account/issues/269) APES CIC staff | [APES CIC staff](#'.$anchors['apes_cic_staff'].') + [Cases](#'.$anchors['plugin_cases'].') + [Tickets](#'.$anchors['plugin_tickets'].') + [Consultations](#'.$anchors['plugin_consultations'].') + Recruitment staff views | `docs/i18n-inventory.md#'.$anchors['apes_cic_staff'].'` |';
        $lines[] = '| [#270](https://github.com/APESCIC/MyAPES-Account/issues/270) Admin shell | [Admin shell](#'.$anchors['admin'].') | `docs/i18n-inventory.md#'.$anchors['admin'].'` |';
        $lines[] = '| [#271](https://github.com/APESCIC/MyAPES-Account/issues/271) Auth / mail / flash | [Auth / emails / notifications / flash / validation](#'.$anchors['auth'].') | `docs/i18n-inventory.md#'.$anchors['auth'].'` |';
        $lines[] = '';
        $lines[] = '## Totals';
        $lines[] = '';
        $lines[] = '| Area | Count |';
        $lines[] = '| --- | ---: |';

        foreach ($labels as $area => $label) {
            $count = (int) ($payload['totals'][$area] ?? 0);
            $anchor = $anchors[$area] ?? $area;
            $lines[] = '| ['.$label.'](#'.$anchor.') | '.$count.' |';
        }

        $lines[] = '| **All (inventory)** | **'.(int) ($payload['totals']['all'] ?? 0).'** |';
        $lines[] = '| Allow-listed (omitted below) | '.(int) ($payload['allowlisted_count'] ?? 0).' |';
        $lines[] = '';
        $lines[] = '_Generated at '.$payload['generated_at'].'_';
        $lines[] = '';

        /** @var list<array<string, mixed>> $findings */
        $findings = $payload['findings'];
        $byArea = [];
        foreach ($findings as $finding) {
            $byArea[$finding['area']][] = $finding;
        }

        foreach ($labels as $area => $label) {
            $anchor = $anchors[$area] ?? $area;
            $issue = $issues[$area] ?? null;
            $issueBit = $issue !== null
                ? ' — extraction [#'.$issue.'](https://github.com/APESCIC/MyAPES-Account/issues/'.$issue.')'
                : '';

            $lines[] = '<a id="'.$anchor.'"></a>';
            $lines[] = '';
            $lines[] = '## '.$label;
            $lines[] = '';
            $lines[] = 'Path anchor for children: `docs/i18n-inventory.md#'.$anchor.'`'.$issueBit.'.';
            $lines[] = '';

            $rows = $byArea[$area] ?? [];
            if ($rows === []) {
                $lines[] = '_No hard-coded findings in this area (or all allow-listed)._';
                $lines[] = '';

                continue;
            }

            $lines[] = '| File | Line | Kind | Snippet | Suggested key | Target |';
            $lines[] = '| --- | ---: | --- | --- | --- | --- |';

            foreach ($rows as $row) {
                $lines[] = '| `'.$this->mdCell($row['path']).'` | '.$row['line'].' | `'.$this->mdCell($row['kind']).'` | '.$this->mdCell($row['snippet']).' | `'.$this->mdCell($row['suggested_key']).'` | `'.$this->mdCell($row['target']).'` |';
            }

            $lines[] = '';
            $lines[] = '**Section total:** '.count($rows);
            $lines[] = '';
        }

        $lines[] = '## Allow-list / false positives';
        $lines[] = '';
        $lines[] = 'Configured in `config/lang_inventory.php`. Matches are omitted from the tables above so extraction PRs stay focused. CI (#276) should load the same config when warning on new hard-coded strings.';
        $lines[] = '';
        $lines[] = 'Common categories: HTTP method tokens, digit-only snippets, HTML entities, decorative glyphs, long path/FQCN-like tokens without spaces.';
        $lines[] = '';
        $lines[] = '## Design notes for #276';
        $lines[] = '';
        $lines[] = '1. Call `HardCodedStringScanner::scan()` (or `inventoryFindings()`).';
        $lines[] = '2. Prefer report-only warnings on **changed files** until extraction waves finish.';
        $lines[] = '3. Re-use `config/lang_inventory.php` allow-list — do not fork a second list.';
        $lines[] = '4. Optional: diff against committed JSON snapshot when one is published for CI.';
        $lines[] = '';

        return implode("\n", $lines);
    }

    private function mdCell(string $value): string
    {
        return str_replace(['|', "\n"], ['\\|', ' '], $value);
    }
}
