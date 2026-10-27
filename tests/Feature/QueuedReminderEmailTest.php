<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SendReminderEmail;
use App\Mail\FollowUpReminderMail;
use App\Models\Category;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ReminderService;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueuedReminderEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_reminder_is_claimed_and_queued_only_once(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 12:00:00'));
        $reminder = $this->makeReminder('2026-10-10 11:00:00');
        Queue::fake();

        $this->artisan('reminders:dispatch')
            ->expectsOutput('Queued 1 reminder email(s).')
            ->assertSuccessful();
        $this->artisan('reminders:dispatch')
            ->expectsOutput('Queued 0 reminder email(s).')
            ->assertSuccessful();

        Queue::assertPushed(SendReminderEmail::class, 1);
        $this->assertNotNull($reminder->fresh()->queued_at);
    }

    public function test_retry_after_success_does_not_send_a_duplicate_email(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 12:00:00'));
        $reminder = $this->makeReminder('2026-10-10 11:00:00');
        Mail::fake();
        $job = new SendReminderEmail($reminder->id);

        $job->handle();
        $job->handle();

        Mail::assertSent(FollowUpReminderMail::class, 1);
        $this->assertNotNull($reminder->fresh()->sent_at);
    }

    public function test_future_completed_and_cancelled_reminders_are_not_queued(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 12:00:00'));
        $future = $this->makeReminder('2026-10-11 11:00:00');
        $completed = $this->makeReminder('2026-10-10 10:00:00');
        $completed->update(['completed_at' => now()]);
        $cancelled = $this->makeReminder('2026-10-10 09:00:00');
        $cancelled->update(['cancelled_at' => now()]);
        Queue::fake();

        $this->artisan('reminders:dispatch')
            ->expectsOutput('Queued 0 reminder email(s).')
            ->assertSuccessful();

        Queue::assertNothingPushed();
        $this->assertNull($future->fresh()->queued_at);
    }

    public function test_changing_a_sent_promise_resets_email_delivery_state(): void
    {
        $this->travelTo(Carbon::parse('2026-10-10 12:00:00'));
        $reminder = $this->makeReminder('2026-10-10 11:00:00');
        $reminder->update([
            'queued_at' => now(),
            'sent_at' => now(),
        ]);
        $event = $reminder->caseEvent;
        $event->update(['promised_follow_up_at' => Carbon::parse('2026-10-12 15:30:00')]);

        app(ReminderService::class)->syncFromEvent($event->fresh(), $reminder->user_id);

        $reminder->refresh();
        $this->assertSame('2026-10-12 15:30', $reminder->due_at->format('Y-m-d H:i'));
        $this->assertNull($reminder->queued_at);
        $this->assertNull($reminder->sent_at);
    }

    private function makeReminder(string $dueAt): Reminder
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
        $event = $case->events()->create([
            'created_by' => $user->id,
            'event_type' => 'promise',
            'occurred_at' => now()->subDay(),
            'summary' => 'Retailer promised a status update.',
            'promised_follow_up_at' => Carbon::parse($dueAt),
        ]);

        app(ReminderService::class)->syncFromEvent($event, $user->id);

        return $event->reminder()->firstOrFail();
    }
}
