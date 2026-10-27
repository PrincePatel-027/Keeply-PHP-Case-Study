<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolutionOutcomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_repaired_outcome_closes_the_case_and_preserves_full_history(): void
    {
        [$user, , , $case] = $this->ownerContext();
        $firstEvent = $case->events()->create([
            'created_by' => $user->id,
            'event_type' => 'contact',
            'occurred_at' => '2026-02-16 10:00:00',
            'summary' => 'Called the seller.',
            'reference' => 'SUP-2048',
        ]);
        $promise = $case->events()->create([
            'created_by' => $user->id,
            'event_type' => 'promise',
            'occurred_at' => '2026-02-17 10:00:00',
            'summary' => 'Seller promised a response.',
            'promised_follow_up_at' => '2026-02-20 10:00:00',
        ]);
        app(ReminderService::class)->syncFromEvent($promise, $user->id);

        $this->actingAs($user)
            ->post(route('cases.outcome.store', $case), [
                'outcome_type' => 'repaired',
                'occurred_at' => '2026-02-19T14:30',
                'summary' => 'The seller replaced the battery at no charge.',
                'resolution_value' => '1250.50',
            ])
            ->assertRedirect(route('cases.show', $case));

        $case->refresh();
        $this->assertSame('resolved', $case->status);
        $this->assertSame('repaired', $case->resolution_type);
        $this->assertSame(125050, $case->resolution_value_minor);
        $this->assertSame('2026-02-19 14:30:00', $case->resolved_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('case_events', [
            'resolution_case_id' => $case->id,
            'event_type' => 'outcome',
            'summary' => 'The seller replaced the battery at no charge.',
        ]);
        $this->assertDatabaseHas('case_events', ['id' => $firstEvent->id]);
        $this->assertNotNull($promise->reminder->fresh()->cancelled_at);

        $this->get(route('cases.show', $case))
            ->assertOk()
            ->assertSee('Outcome recorded')
            ->assertSee('Repaired')
            ->assertSee('INR 1,250.50')
            ->assertSee('The seller replaced the battery at no charge.')
            ->assertSee('Called the seller.');
    }

    public function test_rejected_and_abandoned_outcomes_close_as_unresolved(): void
    {
        [$user, , , $rejectedCase] = $this->ownerContext();
        [, , , $abandonedCase] = $this->ownerContext($user);

        $this->actingAs($user)->post(route('cases.outcome.store', $rejectedCase), [
            'outcome_type' => 'rejected',
            'occurred_at' => '2026-02-19T14:30',
            'summary' => 'The seller rejected the request.',
        ]);
        $this->post(route('cases.outcome.store', $abandonedCase), [
            'outcome_type' => 'abandoned',
            'occurred_at' => '2026-02-20T09:00',
            'summary' => 'No further action was worthwhile.',
        ]);

        $this->assertSame('closed_unresolved', $rejectedCase->fresh()->status);
        $this->assertSame('rejected', $rejectedCase->fresh()->resolution_type);
        $this->assertSame('closed_unresolved', $abandonedCase->fresh()->status);
        $this->assertSame('abandoned', $abandonedCase->fresh()->resolution_type);
    }

    public function test_final_outcome_cannot_be_replaced_or_future_dated(): void
    {
        [$user, , , $case] = $this->ownerContext();

        $this->actingAs($user)->post(route('cases.outcome.store', $case), [
            'outcome_type' => 'refunded',
            'occurred_at' => '2026-02-19T14:30',
            'summary' => 'The seller issued a refund.',
        ]);

        $this->post(route('cases.outcome.store', $case), [
            'outcome_type' => 'replaced',
            'occurred_at' => '2026-02-20T14:30',
            'summary' => 'Must not replace the first outcome.',
        ])->assertSessionHasErrors('outcome_type');

        $this->assertSame('refunded', $case->fresh()->resolution_type);
        $this->assertDatabaseCount('case_events', 1);

        [, , , $futureCase] = $this->ownerContext($user);
        $this->post(route('cases.outcome.store', $futureCase), [
            'outcome_type' => 'repaired',
            'occurred_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'summary' => 'This has not happened yet.',
        ])->assertSessionHasErrors('occurred_at');
        $this->assertNull($futureCase->fresh()->resolution_type);
    }

    public function test_another_workspace_cannot_record_the_outcome(): void
    {
        [, , , $case] = $this->ownerContext();
        [$otherUser] = $this->ownerContext();

        $this->actingAs($otherUser)
            ->post(route('cases.outcome.store', $case), [
                'outcome_type' => 'repaired',
                'occurred_at' => '2026-02-19T14:30',
                'summary' => 'Must not be recorded.',
            ])
            ->assertForbidden();

        $this->assertNull($case->fresh()->resolution_type);
        $this->assertDatabaseCount('case_events', 0);
    }

    private function ownerContext(?User $user = null): array
    {
        $user ??= User::factory()->create();
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
