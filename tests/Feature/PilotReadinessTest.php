<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PilotReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_responses_include_the_browser_security_baseline(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'same-origin')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin')
            ->assertHeader('Cross-Origin-Resource-Policy', 'same-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), geolocation=(), microphone=()')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_authenticated_pages_are_not_stored_in_browser_caches(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('account.show'));

        $response->assertOk();
        $this->assertStringContainsString(
            'no-store',
            (string) $response->headers->get('Cache-Control'),
        );
    }

    public function test_sensitive_form_errors_are_programmatically_associated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('account.show'))
            ->delete(route('account.destroy'), [
                'password' => 'incorrect-password',
                'confirmation' => 'DELETE MY ACCOUNT',
            ])
            ->assertRedirect(route('account.show'));

        $this->get(route('account.show'))
            ->assertOk()
            ->assertSee('aria-describedby="password-error"', false)
            ->assertSee('id="password-error"', false);
    }

    public function test_printing_uses_external_script_and_mobile_navigation_stays_available(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('js/keeply.js');

        $script = file_get_contents(public_path('js/keeply.js'));
        $styles = file_get_contents(public_path('css/keeply.css'));

        $this->assertStringContainsString('[data-print-preview]', $script);
        $this->assertStringContainsString('overflow-x: auto', $styles);
        $this->assertStringNotContainsString(".nav-identity,\n    .nav-link {", $styles);
    }
}
