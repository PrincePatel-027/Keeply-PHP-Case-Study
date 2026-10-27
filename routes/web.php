<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\ActionPackController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\CaseAssessmentController;
use App\Http\Controllers\CaseEventController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\ResolutionCaseController;
use App\Http\Controllers\ResolutionOutcomeController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/verify-email', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/today', [WorkspaceController::class, 'index'])->name('dashboard');
    Route::get('/workspaces/{workspace}', [WorkspaceController::class, 'show'])->name('workspaces.show');

    Route::get('/account', [AccountController::class, 'show'])->name('account.show');
    Route::get('/account/export', [AccountController::class, 'export'])->name('account.export');
    Route::delete('/account', [AccountController::class, 'destroy'])
        ->middleware('throttle:3,1')
        ->name('account.destroy');

    Route::get('/items', [ItemController::class, 'index'])->name('items.index');
    Route::get('/items/create', [ItemController::class, 'create'])->name('items.create');
    Route::post('/items', [ItemController::class, 'store'])->name('items.store');
    Route::get('/items/{item}', [ItemController::class, 'show'])->name('items.show');
    Route::get('/cases', [ResolutionCaseController::class, 'index'])->name('cases.index');
    Route::get('/items/{item}/cases/create', [ResolutionCaseController::class, 'create'])->name('items.cases.create');
    Route::post('/items/{item}/cases', [ResolutionCaseController::class, 'store'])->name('items.cases.store');
    Route::get('/cases/{resolutionCase}', [ResolutionCaseController::class, 'show'])->name('cases.show');
    Route::post('/cases/{resolutionCase}/assessments', [CaseAssessmentController::class, 'store'])
        ->name('cases.assessments.store');
    Route::get('/cases/{resolutionCase}/action-packs/create', [ActionPackController::class, 'create'])
        ->name('cases.action-packs.create');
    Route::post('/cases/{resolutionCase}/action-packs/preview', [ActionPackController::class, 'preview'])
        ->name('cases.action-packs.preview');
    Route::post('/cases/{resolutionCase}/action-packs', [ActionPackController::class, 'store'])
        ->name('cases.action-packs.store');
    Route::get('/action-packs/{actionPack}', [ActionPackController::class, 'download'])
        ->name('action-packs.download');
    Route::patch('/cases/{resolutionCase}/status', [ResolutionCaseController::class, 'updateStatus'])
        ->name('cases.status.update');
    Route::post('/cases/{resolutionCase}/outcome', [ResolutionOutcomeController::class, 'store'])
        ->name('cases.outcome.store');
    Route::post('/cases/{resolutionCase}/events', [CaseEventController::class, 'store'])
        ->name('cases.events.store');
    Route::get('/cases/{resolutionCase}/events/{caseEvent}/edit', [CaseEventController::class, 'edit'])
        ->name('cases.events.edit');
    Route::patch('/cases/{resolutionCase}/events/{caseEvent}', [CaseEventController::class, 'update'])
        ->name('cases.events.update');

    Route::patch('/reminders/{reminder}/complete', [ReminderController::class, 'complete'])
        ->name('reminders.complete');

    Route::post('/items/{item}/documents', [DocumentController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('items.documents.store');
    Route::get('/documents/{document}', [DocumentController::class, 'download'])->name('documents.download');
});
