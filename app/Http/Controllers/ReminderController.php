<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReminderController extends Controller
{
    public function complete(Reminder $reminder): RedirectResponse
    {
        Gate::authorize('update', $reminder);

        if ($reminder->cancelled_at !== null) {
            throw ValidationException::withMessages([
                'reminder' => 'This reminder was cancelled when its promised follow-up was removed.',
            ]);
        }

        if ($reminder->completed_at === null) {
            $reminder->update(['completed_at' => now()]);
        }

        return redirect()->route('dashboard')->with('status', 'Follow-up marked complete.');
    }
}
