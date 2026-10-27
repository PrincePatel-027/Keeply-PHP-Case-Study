<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteAccountRequest;
use App\Services\AccountDeletionService;
use App\Services\AccountExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function show(): View
    {
        return view('account.show');
    }

    public function export(Request $request, AccountExportService $exports): JsonResponse
    {
        $response = response()->json(
            $exports->build($request->user()),
            200,
            [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        );

        return $response->header(
            'Content-Disposition',
            'attachment; filename="keeply-account-export-'.now()->format('Y-m-d').'.json"',
        );
    }

    public function destroy(
        DeleteAccountRequest $request,
        AccountDeletionService $deletions,
    ): RedirectResponse {
        $deletions->request($request->user());

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with(
            'status',
            'Account deletion requested. Your data will be permanently removed after 30 days.',
        );
    }
}
