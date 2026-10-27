<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function index(Request $request): View
    {
        $workspace = $request->user()->workspaces()->firstOrFail();

        return $this->dashboard($workspace, $request->user()->id);
    }

    public function show(Request $request, Workspace $workspace): View
    {
        Gate::authorize('view', $workspace);

        return $this->dashboard($workspace, $request->user()->id);
    }

    private function dashboard(Workspace $workspace, int $userId): View
    {
        $workspace->loadCount([
            'items',
            'documents',
            'resolutionCases as open_cases_count' => fn ($query) => $query->whereNotIn('status', ['resolved', 'closed_unresolved']),
        ]);

        $dueReminders = $workspace->reminders()
            ->pending()
            ->where('user_id', $userId)
            ->where('due_at', '<=', now()->endOfDay())
            ->with(['resolutionCase.item', 'caseEvent'])
            ->orderBy('due_at')
            ->get();

        return view('dashboard', compact('workspace', 'dueReminders'));
    }
}
