<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_and_authentication_surfaces_render(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Everything you own, filed.')
            ->assertSee('css/keeply.css');

        $this->get(route('login'))->assertOk()->assertSee('Return to your cabinet.');
        $this->get(route('register'))->assertOk()->assertSee('Open your Keeply cabinet.');
        $this->get(route('password.request'))->assertOk()->assertSee('Find your account file.');
    }

    public function test_verification_and_empty_workspace_surfaces_render(): void
    {
        $unverified = User::factory()->unverified()->create();
        $this->actingAs($unverified)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertSee('Label this cabinet as yours.');

        $user = User::factory()->create();
        $workspace = Workspace::create([
            'name' => 'Prince Personal',
            'currency' => 'INR',
            'country_code' => 'IN',
        ]);
        $workspace->members()->attach($user, ['role' => 'owner']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Your cabinet is ready for its first record.')
            ->assertSee('Prince Personal');
    }
}
