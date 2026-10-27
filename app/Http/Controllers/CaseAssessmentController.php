<?php

namespace App\Http\Controllers;

use App\Models\ResolutionCase;
use App\Services\AssessmentSnapshotService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CaseAssessmentController extends Controller
{
    public function store(
        Request $request,
        ResolutionCase $resolutionCase,
        AssessmentSnapshotService $snapshotService,
    ): RedirectResponse {
        Gate::authorize('view', $resolutionCase);

        $assessment = $snapshotService->create($resolutionCase, $request->user()->id);

        return redirect()
            ->route('cases.show', $resolutionCase)
            ->with('status', "Assessment snapshot v{$assessment->version} saved.");
    }
}
