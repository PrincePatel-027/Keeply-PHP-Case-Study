<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CaseEvent;
use App\Models\Category;
use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseTimelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_events_display_in_chronological_order_regardless_of_entry_order(): void
    {
        [$user, $case] = $this->caseOwner();

        $this->actingAs($user)->post(route('cases.events.store', $case), [
            'event_type' => 'response',
            'occurred_at' => '2026-10-03T10:00',
            'summary' => 'Support replied with troubleshooting steps.',
        ])->assertSessionHasNoErrors();

        $this->post(route('cases.events.store', $case), [
            'event_type' => 'contact',
            'occurred_at' => '2026-10-01T09:00',
            'channel' => 'email',
            'party_name' => 'Support desk',
            'summary' => 'Sent the first support request.',
        ])->assertSessionHasNoErrors();

        $this->get(route('cases.show', $case))
            ->assertOk()
            ->assertSeeInOrder([
                'Sent the first support request.',
                'Support replied with troubleshooting steps.',
            ]);
    }

    public function test_editing_event_time_reorders_the_chronology(): void
    {
        [$user, $case] = $this->caseOwner();
        $first = $case->events()->create([
            'created_by' => $user->id,
            'event_type' => 'contact',
            'occurred_at' => '2026-10-01 09:00:00',
            'summary' => 'Originally first event.',
        ]);
        $case->events()->create([
            'created_by' => $user->id,
            'event_type' => 'response',
            'occurred_at' => '2026-10-02 09:00:00',
            'summary' => 'Originally second event.',
        ]);

        $this->actingAs($user)->patch(route('cases.events.update', [$case, $first]), [
            'event_type' => 'contact',
            'occurred_at' => '2026-10-03T09:00',
            'summary' => 'Corrected to the later date.',
        ])->assertSessionHasNoErrors();

        $this->get(route('cases.show', $case))
            ->assertSeeInOrder([
                'Originally second event.',
                'Corrected to the later date.',
            ]);
    }

    public function test_expense_and_promised_follow_up_are_stored_as_typed_values(): void
    {
        [$user, $case] = $this->caseOwner();

        $this->actingAs($user)->post(route('cases.events.store', $case), [
            'event_type' => 'promise',
            'occurred_at' => '2026-10-04T11:30',
            'channel' => 'phone',
            'summary' => 'Agent promised a callback after inspection.',
            'reference' => 'CASE-2048',
            'expense' => '349.50',
            'promised_follow_up_at' => '2026-10-12T17:00',
        ])->assertSessionHasNoErrors();

        $event = CaseEvent::firstOrFail();

        $this->assertSame(34950, $event->expense_minor);
        $this->assertSame('2026-10-12 17:00', $event->promised_follow_up_at->format('Y-m-d H:i'));
        $this->assertSame('CASE-2048', $event->reference);
    }

    public function test_another_workspace_cannot_add_or_edit_timeline_entries(): void
    {
        [$owner, $case] = $this->caseOwner();
        [$otherUser] = $this->caseOwner();
        $event = $case->events()->create([
            'created_by' => $owner->id,
            'event_type' => 'note',
            'occurred_at' => '2026-10-01 12:00:00',
            'summary' => 'Private case note.',
        ]);

        $this->actingAs($otherUser)->post(route('cases.events.store', $case), [
            'event_type' => 'note',
            'occurred_at' => '2026-10-02T12:00',
            'summary' => 'Must not be added.',
        ])->assertForbidden();

        $this->patch(route('cases.events.update', [$case, $event]), [
            'event_type' => 'note',
            'occurred_at' => '2026-10-02T12:00',
            'summary' => 'Must not be changed.',
        ])->assertForbidden();

        $this->assertDatabaseCount('case_events', 1);
        $this->assertSame('Private case note.', $event->fresh()->summary);
    }

    private function caseOwner(): array
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
        $case = $workspace->resolutionCases()->create([
            'item_id' => $item->id,
            'opened_by' => $user->id,
            'title' => 'Battery problem',
            'problem_summary' => 'Battery does not hold charge.',
            'desired_outcome' => 'repair',
            'status' => 'active',
            'priority' => 'normal',
            'opened_at' => now(),
        ]);

        return [$user, $case];
    }
}
