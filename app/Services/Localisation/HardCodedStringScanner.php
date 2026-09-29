<?php

namespace App\Services\Localisation;

use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Scans Blade + PHP for hard-coded user-facing strings (#266).
 *
 * Designed for reuse by CI hard-coded warnings (#276): call scan() and filter
 * allowlisted findings, or compare against a baseline inventory JSON.
 */
class HardCodedStringScanner
{
    /**
     * @return list<HardCodedStringFinding>
     */
    public function scan(?string $basePath = null): array
    {
        $basePath = $basePath ?? base_path();
        $findings = [];

        foreach ($this->files($basePath) as $absolutePath) {
            $relative = ltrim(str_replace('\\', '/', Str::after($absolutePath, rtrim($basePath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)), '/');

            if ($this->shouldIgnorePath($relative)) {
                continue;
            }

            $contents = file_get_contents($absolutePath);
            if ($contents === false || $contents === '') {
                continue;
            }

            if (str_ends_with($relative, '.blade.php')) {
                $findings = array_merge($findings, $this->scanBlade($relative, $contents));
            } elseif (str_ends_with($relative, '.php')) {
                $findings = array_merge($findings, $this->scanPhp($relative, $contents));
            }
        }

        usort(
            $findings,
            static fn (HardCodedStringFinding $a, HardCodedStringFinding $b): int => [$a->area, $a->path, $a->line, $a->snippet]
                <=> [$b->area, $b->path, $b->line, $b->snippet],
        );

        return $findings;
    }

    /**
     * @return list<HardCodedStringFinding>
     */
    public function inventoryFindings(?string $basePath = null): array
    {
        return array_values(array_filter(
            $this->scan($basePath),
            static fn (HardCodedStringFinding $finding): bool => ! $finding->allowlisted,
        ));
    }

    /**
     * @return list<string>
     */
    private function files(string $basePath): array
    {
        $paths = [];
        $roots = config('lang_inventory.roots', []);

        foreach ($roots as $root) {
            $absoluteRoot = $basePath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $root);
            if (! is_dir($absoluteRoot)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($absoluteRoot, RecursiveDirectoryIterator::SKIP_DOTS),
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $name = $file->getFilename();
                if (str_ends_with($name, '.blade.php') || (str_ends_with($name, '.php') && ! str_ends_with($name, '.blade.php'))) {
                    $paths[] = $file->getPathname();
                }
            }
        }

        sort($paths);

        return $paths;
    }

    private function shouldIgnorePath(string $relative): bool
    {
        $normalized = str_replace('\\', '/', $relative);

        foreach (config('lang_inventory.ignore_path_prefixes', []) as $prefix) {
            if (str_starts_with($normalized, $prefix)) {
                return true;
            }
        }

        foreach (config('lang_inventory.ignore_path_contains', []) as $needle) {
            if (str_contains($normalized, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<HardCodedStringFinding>
     */
    private function scanBlade(string $relative, string $contents): array
    {
        $findings = [];
        $stripped = $this->preprocessBlade($contents);

        $attributes = config('lang_inventory.blade_attributes', []);
        // `value` is only user-facing on buttons — handled separately.
        $attributes = array_values(array_filter(
            $attributes,
            static fn (string $attribute): bool => strtolower($attribute) !== 'value',
        ));
        $attrPattern = implode('|', array_map(static fn (string $a): string => preg_quote($a, '/'), $attributes));

        if ($attrPattern !== '') {
            if (preg_match_all(
                '/\b('.$attrPattern.')\s*=\s*([\'"])([^\'"{]+)\2/i',
                $stripped,
                $matches,
                PREG_OFFSET_CAPTURE,
            )) {
                foreach ($matches[0] as $index => $full) {
                    $snippet = html_entity_decode(trim($matches[3][$index][0]), ENT_QUOTES | ENT_HTML5);
                    if ($this->looksLikeBladeResidue($snippet) || ! $this->looksUserFacing($snippet)) {
                        continue;
                    }
                    $findings[] = $this->makeFinding(
                        $relative,
                        $this->lineAt($contents, $this->approximateOffset($contents, $snippet, $full[1])),
                        'blade.attribute.'.$matches[1][$index][0],
                        $snippet,
                    );
                }
            }
        }

        // Button / submit values only (form field values are not UI copy).
        if (preg_match_all(
            '/<(?:button\b[^>]*|input\b[^>]*\btype\s*=\s*[\'"](?:submit|button|reset)[\'"][^>]*)\bvalue\s*=\s*([\'"])([^\'"{]+)\1/i',
            $stripped,
            $valueMatches,
            PREG_OFFSET_CAPTURE,
        )) {
            foreach ($valueMatches[2] as $snippetMatch) {
                $snippet = html_entity_decode(trim($snippetMatch[0]), ENT_QUOTES | ENT_HTML5);
                if ($this->looksLikeBladeResidue($snippet) || ! $this->looksUserFacing($snippet)) {
                    continue;
                }
                $findings[] = $this->makeFinding(
                    $relative,
                    $this->lineAt($contents, $this->approximateOffset($contents, $snippet, $snippetMatch[1])),
                    'blade.attribute.value',
                    $snippet,
                );
            }
        }

        // Text nodes between tags after Blade has been stripped.
        if (preg_match_all('/>([^<>]+)</u', $stripped, $textMatches, PREG_OFFSET_CAPTURE)) {
            foreach ($textMatches[1] as $match) {
                $raw = $match[0];
                $snippet = html_entity_decode(trim(preg_replace('/\s+/u', ' ', $raw) ?? $raw), ENT_QUOTES | ENT_HTML5);

                if ($snippet === '' || $this->looksLikeBladeResidue($snippet) || ! $this->looksUserFacing($snippet)) {
                    continue;
                }

                $findings[] = $this->makeFinding(
                    $relative,
                    $this->lineAt($contents, $this->approximateOffset($contents, $snippet, $match[1])),
                    'blade.text',
                    $snippet,
                );
            }
        }

        return $this->uniqueFindings($findings);
    }

    private function preprocessBlade(string $contents): string
    {
        $stripped = preg_replace('/\{\{--.*?--\}\}/s', '', $contents) ?? $contents;
        $stripped = preg_replace('/<!--.*?-->/s', '', $stripped) ?? $stripped;
        $stripped = preg_replace('/<(script|style|code|pre|samp|kbd)\b[^>]*>.*?<\/\1>/is', '', $stripped) ?? $stripped;

        // Remove localisation helpers (and their string args).
        $stripped = preg_replace(
            '/(?:__|@lang|trans|trans_choice)\s*\(\s*([\'"])(?:\\\\.|(?!\1).)*\1(?:\s*,[^)]*)?\)/s',
            '',
            $stripped,
        ) ?? $stripped;

        // Remove Blade echoes.
        $stripped = preg_replace('/\{\{\{?.+?\}?\}\}/s', '', $stripped) ?? $stripped;
        $stripped = preg_replace('/\{!!.+?!!\}/s', '', $stripped) ?? $stripped;

        // Remove @directives including parenthetical forms (may span lines).
        $stripped = preg_replace('/@[a-zA-Z_][\w]*(?:\s*\((?:[^()]+|\([^()]*\))*\))?/s', '', $stripped) ?? $stripped;

        return $stripped;
    }

    private function approximateOffset(string $original, string $snippet, int $fallback): int
    {
        $pos = strpos($original, $snippet);

        return $pos === false ? $fallback : $pos;
    }

    private function looksLikeBladeResidue(string $snippet): bool
    {
        if ($this->looksLikeBladeOnly($snippet)) {
            return true;
        }

        foreach ([
            '@endif', '@endcan', '@else', '@can', '@if', '@foreach', '@forelse',
            'routeIs', 'request(', 'aria-current', '{{', '}}', '{!!', '!!}',
            '}})', ') }}"', "') }", '===', '!==', '&&', '||',
        ] as $needle) {
            if (str_contains($snippet, $needle)) {
                return true;
            }
        }

        // Attribute leftovers after directive strip.
        if (preg_match('/\b(class|href|wire:|x-|src|id|name|type|for|role)\s*=/', $snippet) === 1) {
            return true;
        }

        return false;
    }

    /**
     * @return list<HardCodedStringFinding>
     */
    private function scanPhp(string $relative, string $contents): array
    {
        // Blade already covered; skip non-view PHP that is only a class alias file etc.
        if (str_ends_with($relative, '.blade.php')) {
            return [];
        }

        $findings = [];
        $patterns = [
            'php.flash' => '/->with\(\s*[\'"](?:status|success|error|warning|info)[\'"]\s*,\s*([\'"])((?:\\\\.|(?!\1).)*)\1\s*,?\s*\)/',
            'php.session_flash' => '/(?:session\(\)|\$request->session\(\))->flash\(\s*[\'"](?:status|success|error|warning|info)[\'"]\s*,\s*([\'"])((?:\\\\.|(?!\1).)*)\1\s*\)/',
            'php.abort' => '/\babort\(\s*\d+\s*,\s*([\'"])((?:\\\\.|(?!\1).)*)\1\s*\)/',
            'php.mail_line' => '/->(?:line|subject|greeting|action|error|salutation)\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1/',
            'php.notify_dispatch' => '/->dispatch\(\s*[\'"]notify[\'"]\s*,\s*[^\)]*?([\'"])((?:\\\\.|(?!\1).)*)\1/',
            'php.validation_exception' => '/ValidationException::withMessages\(\s*\[[^\]]*([\'"])((?:\\\\.|(?!\1).)*)\1/',
        ];

        foreach ($patterns as $kind => $pattern) {
            if (preg_match_all($pattern, $contents, $matches, PREG_OFFSET_CAPTURE) === 0) {
                continue;
            }

            foreach ($matches[2] as $snippetMatch) {
                $snippet = $this->normalizePhpString($snippetMatch[0]);
                if (! $this->looksUserFacing($snippet)) {
                    continue;
                }

                $findings[] = $this->makeFinding(
                    $relative,
                    $this->lineAt($contents, $snippetMatch[1]),
                    $kind,
                    $snippet,
                );
            }
        }

        // FormRequest / Request messages() array string values (heuristic).
        if (preg_match('/function\s+messages\s*\(/', $contents) === 1
            && preg_match('/function\s+messages\s*\([^)]*\)\s*(?::\s*array)?\s*\{(?P<body>.*?)\}/s', $contents, $messagesBlock)) {
            if (preg_match_all('/([\'"])(.+?)\1/', $messagesBlock['body'], $msgMatches, PREG_OFFSET_CAPTURE)) {
                $blockOffset = strpos($contents, $messagesBlock['body']);
                foreach ($msgMatches[2] as $snippetMatch) {
                    $snippet = $this->normalizePhpString($snippetMatch[0]);
                    // Skip validation rule keys like "required", "email.unique"
                    if (preg_match('/^[a-z0-9_.]+$/', $snippet) === 1 && ! str_contains($snippet, ' ')) {
                        continue;
                    }
                    if (! $this->looksUserFacing($snippet)) {
                        continue;
                    }
                    $offset = ($blockOffset === false ? 0 : $blockOffset) + $snippetMatch[1];
                    $findings[] = $this->makeFinding(
                        $relative,
                        $this->lineAt($contents, $offset),
                        'php.form_request_message',
                        $snippet,
                    );
                }
            }
        }

        return $this->uniqueFindings($findings);
    }

    private function normalizePhpString(string $snippet): string
    {
        $snippet = stripcslashes($snippet);

        return trim(preg_replace('/\s+/u', ' ', $snippet) ?? $snippet);
    }

    private function looksLikeBladeOnly(string $snippet): bool
    {
        $withoutBlade = preg_replace('/\{\[{0,1}.+?\}{1,2}\}/s', '', $snippet) ?? $snippet;
        $withoutBlade = preg_replace('/@[a-zA-Z]+(?:\([^)]*\))?/', '', $withoutBlade) ?? $withoutBlade;
        $withoutBlade = trim($withoutBlade);

        return $withoutBlade === '' || preg_match('/^[\W\d_]+$/u', $withoutBlade) === 1;
    }

    private function looksUserFacing(string $snippet): bool
    {
        if (mb_strlen($snippet) < 2) {
            return false;
        }

        // Must contain a letter.
        if (preg_match('/\p{L}/u', $snippet) !== 1) {
            return false;
        }

        // Skip FQCN / path-like / config keys without spaces when no Title Case label shape.
        if (preg_match('/^[\\\\a-z0-9_.\/:-]+$/i', $snippet) === 1 && ! preg_match('/\s/', $snippet)) {
            // Allow short Title Case / multi-word labels like "Dashboard" or "Public Login"
            if (preg_match('/^[A-Z][A-Za-z0-9]*(?:[ -][A-Z][A-Za-z0-9]*)*$/', $snippet) !== 1) {
                return false;
            }
            if (str_contains($snippet, '\\') || str_contains($snippet, '::') || str_contains($snippet, '/')) {
                return false;
            }
        }

        // Skip lowercase machine tokens (option values, ids).
        if (preg_match('/^[a-z][a-z0-9_-]*$/', $snippet) === 1) {
            return false;
        }

        // Skip obvious route names.
        if (preg_match('/^(?:[a-z0-9_-]+\.)+[a-z0-9_-]+$/', $snippet) === 1) {
            return false;
        }

        return true;
    }

    private function makeFinding(string $relative, int $line, string $kind, string $snippet): HardCodedStringFinding
    {
        [$area, $plugin] = $this->classify($relative, $kind);
        [$suggestedKey, $target] = $this->suggestKey($area, $plugin, $relative, $snippet, $kind);
        [$allowlisted, $reason] = $this->allowlistMatch($relative, $snippet);

        return new HardCodedStringFinding(
            area: $area,
            path: $relative,
            line: $line,
            kind: $kind,
            snippet: $this->truncate($snippet, 160),
            suggestedKey: $suggestedKey,
            target: $target,
            plugin: $plugin,
            allowlisted: $allowlisted,
            allowlistReason: $reason,
        );
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function classify(string $relative, string $kind): array
    {
        $path = str_replace('\\', '/', $relative);
        $plugin = null;

        if (preg_match('#^plugins/([^/]+)/#', $path, $m) === 1) {
            $plugin = $m[1];
        }

        // Auth / mail / notify take priority for PHP flash/abort/mail kinds in auth controllers.
        if (str_contains($path, '/Notifications/')
            || str_contains($path, '/Mail/')
            || str_starts_with($path, 'app/Notifications/')
            || str_starts_with($path, 'app/Mail/')
            || str_contains($path, '/Auth/')
            || str_starts_with($path, 'resources/views/auth/')
            || str_contains($kind, 'mail')
            || str_contains($kind, 'form_request')
            || str_contains($kind, 'notify')) {
            if ($plugin === null || str_contains($path, '/Auth/') || str_starts_with($path, 'resources/views/auth/')) {
                return ['auth', $plugin];
            }
        }

        if (str_starts_with($path, 'resources/views/admin/')
            || str_contains($path, '/Http/Controllers/Admin/')
            || str_starts_with($path, 'app/Http/Controllers/Admin/')) {
            return ['admin', $plugin];
        }

        if ($plugin !== null) {
            $area = match ($plugin) {
                'cases' => 'plugin_cases',
                'tickets' => 'plugin_tickets',
                'recruitment' => 'plugin_recruitment',
                'consultations' => 'plugin_consultations',
                'pet-profiles' => 'plugin_pet_profiles',
                default => 'other',
            };

            // Public recruitment surfaces also feed #268 — keep under plugin section with path clarity.
            return [$area, $plugin];
        }

        if (str_starts_with($path, 'resources/views/auth/')
            || str_starts_with($path, 'app/Http/Controllers/Auth/')) {
            return ['auth', null];
        }

        if (str_starts_with($path, 'modules/apes-cic/')
            || str_starts_with($path, 'resources/views/sub-cores/')) {
            return ['apes_cic_staff', null];
        }

        if (str_starts_with($path, 'resources/views/')
            || str_starts_with($path, 'app/Http/Controllers/DashboardController.php')
            || str_starts_with($path, 'app/Http/Controllers/ProfileController.php')
            || str_starts_with($path, 'app/Http/Controllers/OnboardingController.php')
            || str_starts_with($path, 'app/Http/Controllers/ChangeLogController.php')
            || str_starts_with($path, 'app/Http/Controllers/SubCoreController.php')) {
            // Profile password flashes stay auth-adjacent but issue groups profile under public extract.
            if (str_contains($kind, 'flash') && str_contains($path, 'ProfileController')) {
                return ['auth', null];
            }

            return ['public', null];
        }

        if (str_contains($kind, 'flash') || str_contains($kind, 'abort')) {
            return ['auth', null];
        }

        return ['other', $plugin];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function suggestKey(string $area, ?string $plugin, string $relative, string $snippet, string $kind): array
    {
        $slug = Str::snake(Str::limit(preg_replace('/[^a-zA-Z0-9]+/', ' ', $snippet) ?? $snippet, 40, ''));
        $slug = trim(preg_replace('/_+/', '_', $slug) ?? $slug, '_');
        if ($slug === '') {
            $slug = 'label';
        }

        $fileStem = Str::snake(pathinfo(str_replace('.blade.php', '', basename($relative)), PATHINFO_FILENAME));

        if ($plugin !== null) {
            $ns = str_replace('-', '_', $plugin);
            $surface = str_contains($relative, '/public/') ? 'public' : (str_contains($relative, '/staff/') ? 'staff' : 'ui');
            $group = match (true) {
                str_contains($kind, 'flash') => 'flash',
                str_contains($kind, 'mail') => 'mail',
                str_contains($kind, 'abort') => 'errors',
                default => $surface,
            };

            return [
                "{$ns}::{$group}.{$fileStem}.{$slug}",
                "plugins/{$plugin}/lang/en_GB/{$group}.php",
            ];
        }

        $file = match ($area) {
            'admin' => 'admin',
            'auth' => match (true) {
                str_contains($kind, 'flash') => 'flash',
                str_contains($kind, 'mail') => 'mail',
                str_contains($kind, 'abort') => 'auth',
                default => 'auth',
            },
            'apes_cic_staff' => 'apes_cic',
            'public' => match (true) {
                str_contains($relative, '/legal/') => 'legal',
                str_contains($relative, '/profile/') => 'profile',
                str_contains($relative, 'layouts/') => 'seo',
                default => 'nav',
            },
            default => 'ui',
        };

        $prefix = match ($area) {
            'admin' => 'admin',
            'auth' => $file,
            'apes_cic_staff' => 'apes_cic',
            'public' => $file,
            default => 'ui',
        };

        return [
            "{$prefix}.{$fileStem}.{$slug}",
            "lang/en_GB/{$file}.php",
        ];
    }

    /**
     * @return array{0: bool, 1: string|null}
     */
    private function allowlistMatch(string $relative, string $snippet): array
    {
        $path = str_replace('\\', '/', $relative);

        foreach (config('lang_inventory.allowlist', []) as $entry) {
            if (isset($entry['path']) && ! str_contains($path, str_replace('\\', '/', (string) $entry['path']))) {
                continue;
            }

            $pattern = $entry['pattern'] ?? null;
            if (is_string($pattern) && $pattern !== '' && preg_match($pattern, $snippet) !== 1) {
                continue;
            }

            if ($pattern === null && ! isset($entry['path'])) {
                continue;
            }

            return [true, $entry['reason'] ?? 'allowlisted'];
        }

        return [false, null];
    }

    private function lineAt(string $contents, int $offset): int
    {
        if ($offset <= 0) {
            return 1;
        }

        return substr_count(substr($contents, 0, $offset), "\n") + 1;
    }

    private function truncate(string $value, int $max): string
    {
        if (mb_strlen($value) <= $max) {
            return $value;
        }

        return mb_substr($value, 0, $max - 1).'…';
    }

    /**
     * @param  list<HardCodedStringFinding>  $findings
     * @return list<HardCodedStringFinding>
     */
    private function uniqueFindings(array $findings): array
    {
        $seen = [];
        $unique = [];

        foreach ($findings as $finding) {
            $key = $finding->path.'|'.$finding->line.'|'.$finding->snippet.'|'.$finding->kind;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $finding;
        }

        return $unique;
    }
}
