<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AuthorizationProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileAccountEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_public_profile_shows_readonly_account_email_next_to_password_block(): void
    {
        $public = User::factory()->create([
            'email' => 'local.public.email@example.com',
            'password' => 'password',
        ]);

        $profile = $this->actingAs($public)->get(route('profile.edit'));
        $html = $profile->getContent();

        $profile->assertOk()
            ->assertSeeText('Account email')
            ->assertSeeText('local.public.email@example.com')
            ->assertSeeText('Notifications and password-reset mail go to this address.')
            ->assertSeeText('Email cannot be changed here.')
            ->assertSee('id="account-email"', false)
            ->assertSeeText('Change password')
            ->assertSee('id="change-password"', false);

        $this->assertAccountEmailIsReadOnly($html, 'local.public.email@example.com');
        $this->assertLessThan(
            strpos($html, 'id="change-password"'),
            strpos($html, 'id="account-email"'),
        );
        $this->assertDoesNotOfferImpersonation($html);
    }

    public function test_directory_public_profile_shows_readonly_email_without_password_form(): void
    {
        $directoryPublic = User::factory()
            ->directoryIdentity('directory-public-email-subject')
            ->create([
                'email' => 'directory.public.email@example.com',
                'password' => 'password',
            ]);

        $profile = $this->actingAs($directoryPublic)->get(route('profile.edit'));
        $html = $profile->getContent();

        $profile->assertOk()
            ->assertSeeText('Account email')
            ->assertSeeText('directory.public.email@example.com')
            ->assertDontSee('name="current_password"', false)
            ->assertDontSee('name="password_confirmation"', false)
            ->assertDontSee('Change password')
            ->assertSeeText('This account uses Cloudron directory sign-in.');

        $this->assertAccountEmailIsReadOnly($html, 'directory.public.email@example.com');
        $this->assertDoesNotOfferImpersonation($html);
    }

    public function test_staff_profile_still_shows_readonly_directory_email(): void
    {
        $staff = User::factory()
            ->protectedRole(AuthorizationProfile::ROLE_STAFF)
            ->create([
                'email' => 'staff.directory.email@example.com',
                'password' => 'password',
            ]);

        $profile = $this->actingAs($staff)->get(route('profile.edit'));
        $html = $profile->getContent();

        $profile->assertOk()
            ->assertSeeText('staff.directory.email@example.com')
            ->assertDontSee('name="current_password"', false)
            ->assertDontSee('Change password');

        $this->assertDoesNotMatchRegularExpression(
            '/<(input|select|textarea)[^>]*\b(name|id)="email"/i',
            $html,
        );
        $this->assertDoesNotOfferImpersonation($html);
    }

    public function test_profile_update_does_not_change_account_email(): void
    {
        $public = User::factory()->create([
            'email' => 'keep.this.email@example.com',
            'password' => 'password',
        ]);

        $this->actingAs($public)
            ->from(route('profile.edit'))
            ->put(route('profile.update'), [
                'preferred_name' => 'Public',
                'email' => 'forged.email@example.com',
                'address_line_1' => '1 Test Street',
                'town_city' => 'London',
                'postcode' => 'SW1A 1AA',
                'mobile_number' => '+447400123456',
                'services' => ['apes-cic'],
                'contact_preferences_confirmed' => '1',
            ])
            ->assertRedirect(route('profile.edit'));

        $public->refresh();
        $this->assertSame('keep.this.email@example.com', $public->email);
    }

    private function assertAccountEmailIsReadOnly(string $html, string $email): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/<(input|select|textarea)[^>]*\b(name|id)="email"/i',
            $html,
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<(input|select|textarea)[^>]*type="email"/i',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/<dd>\s*'.preg_quote($email, '/').'\s*<\/dd>/',
            $html,
        );
    }

    private function assertDoesNotOfferImpersonation(string $html): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/impersonat|log in as|sign in as/i',
            $html,
        );
    }
}
