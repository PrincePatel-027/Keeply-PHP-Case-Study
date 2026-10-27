<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecordResolutionOutcomeRequest;
use App\Models\ResolutionCase;
use App\Services\ResolutionOutcomeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ResolutionOutcomeController extends Controller
{
    public function store(
        RecordResolutionOutcomeRequest $request,
        ResolutionCase $resolutionCase,
        ResolutionOutcomeService $outcomes,
    ): RedirectResponse {
        Gate::authorize('view', $resolutionCase);
        $outcomes->record($resolutionCase, $request->validated(), $request->user()->id);

        return redirect()
            ->route('cases.show', $resolutionCase)
            ->with('status', 'Final outcome recorded. The case history is now closed.');
    }
}
