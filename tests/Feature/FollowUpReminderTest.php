<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FollowUpReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_promised_follow_up_creates_updates_and_cancels_one_reminder(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 12:00:00'));
        [$user, , $case] = $this->caseOwner();

        $this->actingAs($user)->post(route('cases.events.store', $case), [
            'event_type' => 'promise',
            'occurred_at' => '2026-10-09T10:00',
            'summary' => 'Support promised to call back.',
            'promised_follow_up_at' => '2026-10-11T15:00',
        ])->assertSessionHasNoErrors();

        $event = $case->events()->firstOrFail();
        $reminder = Reminder::firstOrFail();
        $this->assertSame($event->id, $reminder->case_event_id);
        $this->assertSame('2026-10-11 15:00', $reminder->due_at->format('Y-m-d H:i'));

        $this->patch(route('cases.events.update', [$case, $event]), [
            'event_type' => 'promise',
            'occurred_at' => '2026-10-09T10:00',
            'summary' => 'Support changed the callback date.',
            'promised_follow_up_at' => '2026-10-12T16:30',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('reminders', 1);
        $this->assertSame('2026-10-12 16:30', $reminder->fresh()->due_at->format('Y-m-d H:i'));

        $this->patch(route('cases.events.update', [$case, $event]), [
            'event_type' => 'note',
            'occurred_at' => '2026-10-09T10:00',
            'summary' => 'No callback was actually promised.',
        ])->assertSessionHasNoErrors();

        $this->assertNotNull($reminder->fresh()->cancelled_at);
    }

    public function test_overdue_follow_up_appears_on_today_and_can_be_completed(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 12:00:00'));
        [$user, , $case] = $this->caseOwner();

        $this->actingAs($user)->post(route('cases.events.store', $case), [
            'event_type' => 'promise',
            'occurred_at' => '2026-10-08T09:00',
            'summary' => 'Retailer promised an inspection update.',
            'promised_follow_up_at' => '2026-10-09T17:00',
        ])->assertSessionHasNoErrors();

        $reminder = Reminder::firstOrFail();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Overdue')
            ->assertSee('Retailer promised an inspection update.');

        $this->patch(route('reminders.complete', $reminder))
            ->assertSessionHasNoErrors();

        $this->assertNotNull($reminder->fresh()->completed_at);
        $this->get(route('dashboard'))
            ->assertDontSee('Retailer promised an inspection update.');
    }

    public function test_future_follow_up_does_not_appear_in_today_actions(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 12:00:00'));
        [$user, , $case] = $this->caseOwner();

        $this->actingAs($user)->post(route('cases.events.store', $case), [
            'event_type' => 'promise',
            'occurred_at' => '2026-10-10T11:00',
            'summary' => 'Future callback should stay out of Today.',
            'promised_follow_up_at' => '2026-10-11T09:00',
        ])->assertSessionHasNoErrors();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Future callback should stay out of Today.');
    }

    public function test_another_workspace_cannot_complete_the_reminder(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 12:00:00'));
        [$owner, , $case] = $this->caseOwner();
        [$otherUser] = $this->caseOwner();

        $this->actingAs($owner)->post(route('cases.events.store', $case), [
            'event_type' => 'promise',
            'occurred_at' => '2026-10-09T09:00',
            'summary' => 'Private reminder.',
            'promised_follow_up_at' => '2026-10-10T09:00',
        ])->assertSessionHasNoErrors();

        $reminder = Reminder::firstOrFail();

        $this->actingAs($otherUser)
            ->patch(route('reminders.complete', $reminder))
            ->assertForbidden();

        $this->assertNull($reminder->fresh()->completed_at);
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

        return [$user, $workspace, $case];
    }
}
