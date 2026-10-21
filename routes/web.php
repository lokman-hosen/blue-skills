<?php

use App\Http\Controllers\Admin\DesignSystemController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Public\JobController as PublicJobController;
use App\Http\Controllers\Shared\LocaleController;
use App\Http\Controllers\Shared\MessageReportController;
use App\Http\Controllers\Shared\NotificationController;
use App\Http\Controllers\Shared\PrivateMediaController;
use App\Http\Controllers\Shared\SwitchOrganizationController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'public/home')->name('home');

// Find Jobs and the Job Truth Card (Module 3). Published jobs are public.
Route::get('jobs', [PublicJobController::class, 'index'])->name('jobs.index');
Route::get('jobs/{job}', [PublicJobController::class, 'show'])->name('jobs.show');
Route::post('jobs/{job}/report', [PublicJobController::class, 'report'])->middleware(['auth', 'throttle:5,1'])->name('jobs.report');

Route::post('locale', [LocaleController::class, 'update'])->name('locale.update');

Route::post('messages/{message}/report', MessageReportController::class)->middleware(['auth', 'throttle:10,1'])->name('messages.report');

Route::middleware(['auth', 'verified', 'staff.2fa'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::post('organizations/{organization}/switch', SwitchOrganizationController::class)
        ->name('organizations.switch');

    // In-app notifications (Module 5).
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('notifications/{notification}/open', [NotificationController::class, 'open'])->whereUuid('notification')->name('notifications.open');
});

// Private media: signed, short-lived link plus a policy check on the owning model.
Route::get('media/{media:uuid}', PrivateMediaController::class)
    ->middleware(['auth', 'signed'])
    ->name('media.show');

Route::middleware(['auth', 'can:viewDesignSystem', 'staff.2fa'])->prefix('_design')->name('design.')->group(function () {
    Route::get('/', [DesignSystemController::class, 'index'])->name('index');
    Route::get('layouts/{layout}', [DesignSystemController::class, 'layout'])
        ->whereIn('layout', DesignSystemController::LAYOUTS)
        ->name('layout');
});

require __DIR__.'/settings.php';
require __DIR__.'/worker.php';
require __DIR__.'/organization.php';
require __DIR__.'/admin.php';
