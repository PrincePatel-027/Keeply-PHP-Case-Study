<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\ResolutionCase;
use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolutionCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_and_view_a_case_for_a_product(): void
    {
        [$user, $workspace, $item] = $this->productOwner();

        $this->actingAs($user)
            ->post(route('items.cases.store', $item), [
                'title' => 'Battery no longer holds charge',
                'problem_summary' => 'The laptop turns off after twenty minutes even after a full charge.',
                'failure_date' => '2026-10-01',
                'desired_outcome' => 'repair',
                'priority' => 'high',
            ])
            ->assertSessionHasNoErrors();

        $case = ResolutionCase::firstOrFail();

        $this->assertSame($workspace->id, $case->workspace_id);
        $this->assertSame($item->id, $case->item_id);
        $this->assertSame('active', $case->status);
        $this->assertSame('repair', $case->desired_outcome);

        $this->get(route('cases.show', $case))
            ->assertOk()
            ->assertSee('Battery no longer holds charge')
            ->assertSee('Active')
            ->assertSee('Waiting on business');
    }

    public function test_case_rejects_future_failure_dates(): void
    {
        [$user, , $item] = $this->productOwner();

        $this->actingAs($user)
            ->post(route('items.cases.store', $item), [
                'title' => 'Future problem',
                'problem_summary' => 'This date cannot be factual yet.',
                'failure_date' => now()->addDay()->format('Y-m-d'),
                'desired_outcome' => 'repair',
                'priority' => 'normal',
            ])
            ->assertSessionHasErrors('failure_date');

        $this->assertDatabaseCount('resolution_cases', 0);
    }

    public function test_only_allowed_case_transitions_are_applied(): void
    {
        [$user, , $item] = $this->productOwner();
        $case = $this->createCase($user, $item);

        $this->actingAs($user)
            ->patch(route('cases.status.update', $case), ['status' => 'waiting_on_business'])
            ->assertSessionHasNoErrors();
        $this->assertSame('waiting_on_business', $case->fresh()->status);

        $this->patch(route('cases.status.update', $case), ['status' => 'draft'])
            ->assertSessionHasErrors('status');
        $this->assertSame('waiting_on_business', $case->fresh()->status);

        $this->patch(route('cases.status.update', $case), ['status' => 'closed_unresolved'])
            ->assertSessionHasNoErrors();
        $this->assertSame('closed_unresolved', $case->fresh()->status);
        $this->assertNotNull($case->fresh()->resolved_at);
    }

    public function test_another_workspace_cannot_open_or_view_the_case(): void
    {
        [$owner, , $item] = $this->productOwner();
        [$otherUser] = $this->productOwner();
        $case = $this->createCase($owner, $item);

        $this->actingAs($otherUser)
            ->get(route('cases.show', $case))
            ->assertForbidden();

        $this->post(route('items.cases.store', $item), [
            'title' => 'Unauthorised case',
            'problem_summary' => 'Must not be saved.',
            'desired_outcome' => 'other',
            'priority' => 'normal',
        ])->assertForbidden();

        $this->assertDatabaseCount('resolution_cases', 1);
    }

    private function createCase(User $user, Item $item): ResolutionCase
    {
        $this->actingAs($user)->post(route('items.cases.store', $item), [
            'title' => 'Display failure',
            'problem_summary' => 'The display flickers during normal use.',
            'failure_date' => '2026-10-02',
            'desired_outcome' => 'replacement',
            'priority' => 'normal',
        ])->assertSessionHasNoErrors();

        return ResolutionCase::latest('id')->firstOrFail();
    }

    private function productOwner(): array
    {
        $this->seed(CategorySeeder::class);
        $user = User::factory()->create();
        $workspace = Workspace::create([
            'name' => $user->name."'s workspace",
            'currency' => 'INR',
            'country_code' => 'IN',
        ]);
        $workspace->members()->attach($user, ['role' => 'owner']);
        $category = Category::where('key', 'generic')->firstOrFail();
        $item = $workspace->items()->create([
            'category_id' => $category->id,
            'name' => 'Work laptop',
            'currency' => 'INR',
        ]);

        return [$user, $workspace, $item];
    }
}
