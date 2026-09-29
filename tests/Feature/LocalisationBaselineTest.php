<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocalisationBaselineTest extends TestCase
{
    public function test_application_locale_is_en_gb(): void
    {
        $this->assertSame('en_GB', config('app.locale'));
        $this->assertSame('en', config('app.fallback_locale'));
        $this->assertSame('en_GB', config('app.faker_locale'));
        $this->assertSame('en_GB', app()->getLocale());
    }

    public function test_framework_validation_message_resolves_from_en_gb(): void
    {
        $this->assertSame(
            'The :attribute field contains an unauthorised value.',
            __('validation.can'),
        );
        $this->assertSame(
            'email address',
            __('validation.attributes.email'),
        );
        $this->assertFileExists(lang_path('en_GB/auth.php'));
        $this->assertFileExists(lang_path('en_GB/pagination.php'));
        $this->assertFileExists(lang_path('en_GB/passwords.php'));
        $this->assertFileExists(lang_path('en_GB/validation.php'));
        $this->assertFileExists(lang_path('en/validation.php'));
    }

    public function test_html_lang_attribute_uses_en_gb(): void
    {
        $this->get(route('health'))
            ->assertOk();

        $this->get('/')
            ->assertOk()
            ->assertSee('lang="en-GB"', false);
    }
}
