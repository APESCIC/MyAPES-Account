<?php

namespace Tests\Feature;

use App\Console\Commands\LangCheckCommand;
use App\Services\Localisation\TranslationKeyChecker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class TranslationKeyHardFailTest extends TestCase
{
    public function test_lang_check_hard_fail_succeeds_on_current_tree(): void
    {
        $exit = Artisan::call('lang:check', ['--fail-on-missing' => true]);

        $this->assertSame(LangCheckCommand::SUCCESS, $exit, Artisan::output());
    }

    public function test_missing_literal_key_is_detected_and_fails_hard_fail_mode(): void
    {
        $checker = new TranslationKeyChecker;
        $fixtureKey = 'wave4.missing.key.for.hard_fail.'.uniqid('', true);
        $fixtureDir = storage_path('framework/testing/lang-check-'.uniqid());
        File::ensureDirectoryExists($fixtureDir.'/app');
        File::ensureDirectoryExists($fixtureDir.'/lang/en_GB');
        File::put($fixtureDir.'/lang/en_GB/auth.php', "<?php\n\nreturn [];\n");
        File::put($fixtureDir.'/app/Example.php', "<?php\necho __('{$fixtureKey}');\n");

        try {
            config()->set('lang_check.scan_roots', ['app']);
            $report = $checker->check($fixtureDir);

            $missingKeys = array_column($report['missing_keys'], 'key');
            $this->assertContains($fixtureKey, $missingKeys);

            $usage = $report['missing_keys'][0];
            $this->assertArrayHasKey('file', $usage);
            $this->assertArrayHasKey('line', $usage);
            $this->assertSame($fixtureKey, $usage['key']);
        } finally {
            File::deleteDirectory($fixtureDir);
            config()->set('lang_check.scan_roots', [
                'app',
                'resources/views',
                'routes',
                'plugins',
                'modules',
            ]);
        }
    }

    public function test_unused_keys_are_reported_without_failing_default_check(): void
    {
        $exit = Artisan::call('lang:check');
        $this->assertSame(LangCheckCommand::SUCCESS, $exit);

        $report = (new TranslationKeyChecker)->check();
        $this->assertIsArray($report['unused_keys']);
    }

    public function test_plugin_namespaced_keys_resolve_for_checker(): void
    {
        $checker = new TranslationKeyChecker;
        $extracted = $checker->extractKeysFromContents(
            "<?php echo __('tickets::plugin.name');",
            'app/Example.php',
        );

        $this->assertSame('tickets::plugin.name', $extracted[0]['key']);
        $this->assertTrue(Lang::has('tickets::plugin.name', 'en_GB', false));
    }
}
