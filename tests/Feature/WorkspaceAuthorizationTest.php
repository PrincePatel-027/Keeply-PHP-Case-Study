<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkspaceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_the_workspace_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_unverified_user_cannot_open_the_workspace_dashboard(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_member_can_open_their_workspace(): void
    {
        [$user, $workspace] = $this->memberWithWorkspace();

        $this->actingAs($user)
            ->get(route('workspaces.show', $workspace))
            ->assertOk()
            ->assertSee($workspace->name);
    }

    public function test_user_cannot_open_another_users_workspace(): void
    {
        [, $workspace] = $this->memberWithWorkspace();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->get(route('workspaces.show', $workspace))
            ->assertForbidden();
    }

    private function memberWithWorkspace(): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::create([
            'name' => 'Personal workspace',
            'currency' => 'INR',
            'country_code' => 'IN',
        ]);
        $workspace->members()->attach($user, ['role' => 'owner']);

        return [$user, $workspace];
    }
}
