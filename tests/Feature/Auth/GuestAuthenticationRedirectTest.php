<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class GuestAuthenticationRedirectTest extends TestCase
{
    public function test_browser_guests_are_redirected_to_public_login_with_the_intended_url(): void
    {
        foreach (['dashboard', 'profile.edit', 'admin.index'] as $routeName) {
            $this->flushSession();

            $protectedUrl = route($routeName);
            $response = $this->get($protectedUrl);

            $response->assertRedirect(route('public.login'));
            $response->assertSessionHas('url.intended', $protectedUrl);
        }
    }

    public function test_json_guests_receive_an_unauthorized_response_without_a_redirect(): void
    {
        $this->getJson(route('dashboard'))
            ->assertUnauthorized()
            ->assertHeaderMissing('Location')
            ->assertExactJson([
                'message' => 'Unauthenticated.',
            ]);
    }
}
