<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResolutionCaseRequest;
use App\Http\Requests\UpdateCaseStatusRequest;
use App\Models\Item;
use App\Models\ResolutionCase;
use App\Services\CaseStatusService;
use App\Services\CoverageCalculator;
use App\Services\EvidenceChecklistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ResolutionCaseController extends Controller
{
    public function index(Request $request): View
    {
        $workspace = $request->user()->workspaces()->firstOrFail();
        $cases = $workspace->resolutionCases()->with('item')->latest('opened_at')->get();
        $items = $workspace->items()->orderBy('name')->get();

        return view('cases.index', compact('workspace', 'cases', 'items'));
    }

    public function create(Item $item): View
    {
        Gate::authorize('view', $item);
        $item->load('category');

        return view('cases.create', compact('item'));
    }

    public function store(StoreResolutionCaseRequest $request, Item $item): RedirectResponse
    {
        Gate::authorize('view', $item);
        $validated = $request->validated();

        $case = $item->workspace->resolutionCases()->create([
            'item_id' => $item->id,
            'opened_by' => $request->user()->id,
            'title' => $validated['title'],
            'problem_summary' => $validated['problem_summary'],
            'failure_date' => $validated['failure_date'] ?? null,
            'desired_outcome' => $validated['desired_outcome'],
            'priority' => $validated['priority'],
            'status' => 'active',
            'opened_at' => now(),
        ]);

        return redirect()->route('cases.show', $case)->with('status', 'Resolution case opened.');
    }

    public function show(
        ResolutionCase $resolutionCase,
        CaseStatusService $statusService,
        CoverageCalculator $coverageCalculator,
        EvidenceChecklistService $evidenceChecklistService,
    ): View {
        Gate::authorize('view', $resolutionCase);
        $resolutionCase->load([
            'item.category',
            'item.documents',
            'events.reminder',
            'assessments.createdBy',
            'actionPacks.pdfDocument',
        ]);
        $allowedTransitions = $statusService->allowedTransitions($resolutionCase);
        $coverage = $coverageCalculator->calculate($resolutionCase->item, $resolutionCase);
        $evidenceChecklist = $evidenceChecklistService->build($resolutionCase);

        return view('cases.show', compact(
            'resolutionCase',
            'allowedTransitions',
            'coverage',
            'evidenceChecklist',
        ));
    }

    public function updateStatus(
        UpdateCaseStatusRequest $request,
        ResolutionCase $resolutionCase,
        CaseStatusService $statusService,
    ): RedirectResponse {
        Gate::authorize('view', $resolutionCase);
        $statusService->transition($resolutionCase, $request->validated('status'));

        return redirect()->route('cases.show', $resolutionCase)->with('status', 'Case status updated.');
    }
}
