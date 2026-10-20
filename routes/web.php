<?php

use App\Http\Controllers\Admin\DesignSystemController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Shared\LocaleController;
use App\Http\Controllers\Shared\PrivateMediaController;
use App\Http\Controllers\Shared\SwitchOrganizationController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'public/home')->name('home');

Route::post('locale', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware(['auth', 'verified', 'staff.2fa'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::post('organizations/{organization}/switch', SwitchOrganizationController::class)
        ->name('organizations.switch');
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
