<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoverageDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_case_displays_deadlines_states_and_source_facts(): void
    {
        [$user, $workspace, $category] = $this->ownerContext();
        $item = $workspace->items()->create([
            'category_id' => $category->id,
            'name' => 'Work laptop',
            'purchase_date' => '2026-01-01',
            'return_days' => 30,
            'warranty_months' => 12,
            'currency' => 'INR',
        ]);
        $case = $workspace->resolutionCases()->create([
            'item_id' => $item->id,
            'opened_by' => $user->id,
            'title' => 'Battery problem',
            'problem_summary' => 'Battery no longer holds charge.',
            'failure_date' => '2026-02-15',
            'desired_outcome' => 'repair',
            'status' => 'active',
            'priority' => 'normal',
            'opened_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('cases.show', $case))
            ->assertOk()
            ->assertSee('Return position')
            ->assertSee('Warranty position')
            ->assertSee('Expired')
            ->assertSee('Active')
            ->assertSee('31 Jan 2026')
            ->assertSee('1 Jan 2027')
            ->assertSee('Problem date');
    }

    public function test_missing_coverage_facts_are_shown_as_unknown(): void
    {
        [$user, $workspace, $category] = $this->ownerContext();
        $item = $workspace->items()->create([
            'category_id' => $category->id,
            'name' => 'Unknown purchase',
            'currency' => 'INR',
        ]);
        $case = $workspace->resolutionCases()->create([
            'item_id' => $item->id,
            'opened_by' => $user->id,
            'title' => 'Unknown coverage',
            'problem_summary' => 'No purchase facts are available.',
            'desired_outcome' => 'other',
            'status' => 'active',
            'priority' => 'normal',
            'opened_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('cases.show', $case))
            ->assertOk()
            ->assertSee('Unknown')
            ->assertSee('the purchase date is missing')
            ->assertSee('Cannot calculate');
    }

    private function ownerContext(): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::create([
            'name' => 'Personal workspace',
            'currency' => 'INR',
            'country_code' => 'IN',
        ]);
        $workspace->members()->attach($user, ['role' => 'owner']);
        $category = Category::create([
            'key' => 'test-category',
            'name' => 'Test product',
            'is_active' => true,
        ]);

        return [$user, $workspace, $category];
    }
}
