<?php

namespace Tests\Feature;

use App\Services\Localisation\HardCodedStringInventoryReporter;
use App\Services\Localisation\HardCodedStringScanner;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class HardCodedStringInventoryTest extends TestCase
{
    public function test_scanner_finds_blade_text_flash_and_abort_strings(): void
    {
        $fixture = storage_path('framework/testing/lang-inventory-fixture-'.uniqid());
        File::ensureDirectoryExists($fixture.'/resources/views/auth');
        File::ensureDirectoryExists($fixture.'/app/Http/Controllers/Auth');
        File::ensureDirectoryExists($fixture.'/config');

        File::put($fixture.'/resources/views/auth/example.blade.php', <<<'BLADE'
            <h1>Verify your email</h1>
            <button type="submit">{{ __('auth.verify') }}</button>
            <input type="text" placeholder="Email address" aria-label="Email address">
            <p>@lang('auth.ready')</p>
        BLADE);

        File::put($fixture.'/app/Http/Controllers/Auth/ExampleController.php', <<<'PHP'
            <?php
            namespace App\Http\Controllers\Auth;
            class ExampleController {
                public function store() {
                    abort(403, 'This account is suspended.');
                    return redirect()->route('home')->with('status', 'Profile updated.');
                }
            }
        PHP);

        File::copy(config_path('lang_inventory.php'), $fixture.'/config/lang_inventory.php');

        $previous = config('lang_inventory');
        config([
            'lang_inventory' => array_merge($previous, [
                'roots' => [
                    'resources/views',
                    'app/Http',
                ],
            ]),
        ]);

        try {
            $findings = (new HardCodedStringScanner)->inventoryFindings($fixture);
        } finally {
            config(['lang_inventory' => $previous]);
            File::deleteDirectory($fixture);
        }

        $snippets = array_map(static fn ($finding) => $finding->snippet, $findings);

        $this->assertContains('Verify your email', $snippets);
        $this->assertContains('Email address', $snippets);
        $this->assertContains('This account is suspended.', $snippets);
        $this->assertContains('Profile updated.', $snippets);
        $this->assertNotContains('auth.verify', $snippets);
        $this->assertNotContains('auth.ready', $snippets);

        foreach ($findings as $finding) {
            if ($finding->snippet === 'Verify your email') {
                $this->assertSame('auth', $finding->area);
                $this->assertSame('blade.text', $finding->kind);
            }
            if ($finding->snippet === 'Profile updated.') {
                $this->assertSame('auth', $finding->area);
                $this->assertSame('php.flash', $finding->kind);
            }
        }
    }

    public function test_committed_inventory_document_and_section_anchors_exist(): void
    {
        $path = base_path('docs/i18n-inventory.md');
        $this->assertFileExists($path);

        $markdown = File::get($path);
        $this->assertIsString($markdown);

        foreach ([
            'public-268',
            'apes-cic-staff-269',
            'admin-shell-270',
            'auth-emails-flash-validation-271',
            'plugin-cases',
            'plugin-tickets',
            'plugin-recruitment',
            'plugin-consultations',
            'plugin-pet-profiles',
        ] as $anchor) {
            $this->assertStringContainsString('id="'.$anchor.'"', $markdown);
            $this->assertStringContainsString('docs/i18n-inventory.md#'.$anchor, $markdown);
        }

        $this->assertStringContainsString('php artisan lang:inventory --write', $markdown);
        $this->assertStringContainsString('config/lang_inventory.php', $markdown);
        $this->assertStringContainsString('#268', $markdown);
        $this->assertStringContainsString('#276', $markdown);
    }

    public function test_inventory_command_writes_markdown_and_json(): void
    {
        $markdownRelative = 'storage/app/lang-inventory-test.md';
        $jsonRelative = 'storage/app/lang-inventory-test.json';
        $markdown = base_path($markdownRelative);
        $json = base_path($jsonRelative);

        config([
            'lang_inventory.markdown_path' => $markdownRelative,
            'lang_inventory.json_path' => $jsonRelative,
        ]);

        try {
            $this->artisan('lang:inventory', ['--write' => true])
                ->assertSuccessful();

            $this->assertFileExists($markdown);
            $this->assertFileExists($json);

            $payload = json_decode(File::get($json), true);
            $this->assertIsArray($payload);
            $this->assertArrayHasKey('totals', $payload);
            $this->assertArrayHasKey('findings', $payload);
            $this->assertArrayHasKey('all', $payload['totals']);
            $this->assertGreaterThan(0, $payload['totals']['all']);
            $this->assertStringContainsString('Hard-coded UI string inventory', File::get($markdown));
        } finally {
            foreach ([$markdown, $json] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }
    }

    public function test_reporter_marks_allowlisted_findings(): void
    {
        $payload = (new HardCodedStringInventoryReporter)->build();

        $this->assertArrayHasKey('allowlisted_count', $payload);
        $this->assertIsInt($payload['allowlisted_count']);

        foreach ($payload['findings'] as $finding) {
            $this->assertFalse($finding['allowlisted']);
        }
    }
}
