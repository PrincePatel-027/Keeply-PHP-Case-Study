<?php

namespace Tests\Feature;

use App\Models\CaseAssessment;
use App\Models\Category;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class AssessmentSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_later_item_edits_do_not_change_an_old_assessment(): void
    {
        [$user, , $item, $case] = $this->ownerContext();

        $this->actingAs($user)
            ->post(route('cases.assessments.store', $case))
            ->assertRedirect(route('cases.show', $case));

        $first = CaseAssessment::firstOrFail();
        $this->assertSame(1, $first->version);
        $this->assertSame('2026-01-01', $first->snapshot['item']['purchase_date']);
        $this->assertSame('2026-01-31', $first->snapshot['coverage']['return']['deadline']);
        $this->assertSame('expired', $first->snapshot['coverage']['return']['state']);

        $item->update([
            'purchase_date' => '2026-02-01',
            'return_days' => 60,
            'warranty_months' => 1,
        ]);

        $this->post(route('cases.assessments.store', $case))
            ->assertRedirect(route('cases.show', $case));

        $first->refresh();
        $second = CaseAssessment::where('version', 2)->firstOrFail();

        $this->assertSame('2026-01-01', $first->snapshot['item']['purchase_date']);
        $this->assertSame('2026-01-31', $first->snapshot['coverage']['return']['deadline']);
        $this->assertSame('2026-02-01', $second->snapshot['item']['purchase_date']);
        $this->assertSame('2026-04-02', $second->snapshot['coverage']['return']['deadline']);
        $this->assertDatabaseCount('case_assessments', 2);

        $this->get(route('cases.show', $case))
            ->assertOk()
            ->assertSee('Assessment v2')
            ->assertSee('Assessment v1')
            ->assertSee('31 Jan 2026')
            ->assertSee('2 Apr 2026');
    }

    public function test_saved_assessment_cannot_be_updated(): void
    {
        [$user, , , $case] = $this->ownerContext();
        $this->actingAs($user)->post(route('cases.assessments.store', $case));
        $assessment = CaseAssessment::firstOrFail();

        $this->expectException(LogicException::class);
        $assessment->update(['version' => 99]);
    }

    public function test_another_workspace_cannot_save_an_assessment(): void
    {
        [, , , $case] = $this->ownerContext();
        [$otherUser] = $this->ownerContext();

        $this->actingAs($otherUser)
            ->post(route('cases.assessments.store', $case))
            ->assertForbidden();

        $this->assertDatabaseCount('case_assessments', 0);
    }

    private function ownerContext(): array
    {
        $user = User::factory()->create();
        $workspace = Workspace::create([
            'name' => $user->name."'s workspace",
            'currency' => 'INR',
            'country_code' => 'IN',
        ]);
        $workspace->members()->attach($user, ['role' => 'owner']);
        $category = Category::create([
            'key' => 'test-'.str()->random(8),
            'name' => 'Test product',
            'is_active' => true,
        ]);
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

        return [$user, $workspace, $item, $case];
    }
}
