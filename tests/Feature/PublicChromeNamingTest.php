<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicChromeNamingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function platformChromePages(): array
    {
        return [
            'home' => ['/', 'Welcome | MyAPES Core'],
            'privacy' => ['/privacy', 'Privacy notice | MyAPES Core'],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function accountSurfacePages(): array
    {
        return [
            'public login' => ['/login', 'Public Login | MyAPES Account'],
            'register' => ['/register', 'Register | MyAPES Account'],
            'staff login' => ['/staff/login', 'Staff Login | MyAPES Account'],
            'forgot password' => ['/forgot-password', 'Forgot password | MyAPES Account'],
            'reset password' => ['/reset-password/test-token', 'Reset password | MyAPES Account'],
        ];
    }

    #[DataProvider('platformChromePages')]
    public function test_guest_platform_pages_use_core_titles_and_chrome(string $path, string $title): void
    {
        $response = $this->get($path);

        $response->assertOk();
        $this->assertSeesPlatformChrome($response);
        $response->assertSee('<title>'.$title.'</title>', false);
        $response->assertDontSeeText('MyAPES Account');
    }

    #[DataProvider('accountSurfacePages')]
    public function test_login_and_register_pages_keep_account_titles_with_core_chrome(string $path, string $title): void
    {
        $response = $this->get($path);

        $response->assertOk();
        $this->assertSeesPlatformChrome($response);
        $response->assertSee('<title>'.$title.'</title>', false);
        $response->assertSeeText('MyAPES Account');
    }

    public function test_pwa_manifest_names_the_platform_myapes_core(): void
    {
        $manifest = json_decode(
            (string) file_get_contents(public_path('site.webmanifest')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame('MyAPES Core', $manifest['name']);
        $this->assertSame('MyAPES', $manifest['short_name']);
        $this->assertSame('MyAPES Core web application', $manifest['description']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('site.webmanifest', false);
    }

    public function test_signed_in_onboarding_uses_core_title_and_chrome(): void
    {
        $user = User::factory()->create([
            'onboarding_completed_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('onboarding.edit'));

        $response->assertOk();
        $this->assertSeesPlatformChrome($response);
        $response->assertSee('<title>Complete account setup | MyAPES Core</title>', false);
        $response->assertDontSeeText('MyAPES Account');
    }

    public function test_email_verification_keeps_account_title_with_core_chrome(): void
    {
        $user = User::factory()->unverified()->create([
            'onboarding_completed_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertOk();
        $this->assertSeesPlatformChrome($response);
        $response->assertSee('<title>Verify email | MyAPES Account</title>', false);
        $response->assertSeeText('MyAPES Account');
    }

    private function assertSeesPlatformChrome(TestResponse $response): void
    {
        $response
            ->assertSee('property="og:title" content="MyAPES Core"', false)
            ->assertSee('property="og:site_name" content="MyAPES Core"', false)
            ->assertSee('name="twitter:title" content="MyAPES Core"', false)
            ->assertSee('name="application-name" content="MyAPES Core"', false)
            ->assertSee('name="apple-mobile-web-app-title" content="MyAPES Core"', false)
            ->assertSee('alt="MyAPES Core"', false)
            ->assertSee('<span><strong>MyAPES</strong> Core</span>', false)
            ->assertSee('rel="manifest"', false)
            ->assertSee('site.webmanifest', false);
    }
}
