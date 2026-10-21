<?php

use App\Http\Controllers\Admin\EvidenceReviewController;
use App\Http\Controllers\Admin\JobReviewController;
use App\Http\Controllers\Admin\MandateReviewController;
use App\Http\Controllers\Admin\OrganizationReviewController;
use App\Http\Controllers\Admin\SkillShowModerationController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StaffInvitationAcceptanceController;
use Illuminate\Support\Facades\Route;

// Staff management (Module 1D). Staff screens need confirmed 2FA (D-035).
Route::middleware(['auth', 'verified', 'user.type:staff', 'staff.2fa'])->prefix('admin')->name('admin.')->group(function () {
    Route::controller(StaffController::class)->prefix('staff')->name('staff.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('invitations', 'invite')->middleware('throttle:30,1')->name('invitations.store');
        Route::delete('invitations/{invitation}', 'revoke')->name('invitations.destroy');
        Route::patch('members/{member}', 'updateRole')->name('members.update');
        Route::delete('members/{member}', 'deactivate')->name('members.destroy');
    });

    // Organization registration review (Module 3).
    Route::controller(OrganizationReviewController::class)->prefix('organizations')->name('organizations.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('reviews/{review}', 'show')->name('show');
        Route::patch('reviews/{review}', 'decide')->name('decide');
    });

    // Job review (Module 3).
    Route::controller(JobReviewController::class)->prefix('jobs')->name('jobs.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('reviews/{review}', 'show')->name('show');
        Route::patch('reviews/{review}', 'decide')->name('decide');
    });

    // Agency mandate review (Module 6, D-074).
    Route::controller(MandateReviewController::class)->prefix('mandates')->name('mandates.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('reviews/{review}', 'show')->name('show');
        Route::patch('reviews/{review}', 'decide')->name('decide');
    });

    // SkillShow moderation (Module 2, product rule 3).
    // Worker evidence (Module 8, D-090).
    Route::controller(EvidenceReviewController::class)->prefix('evidence')->name('evidence.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('{evidence}', 'show')->name('show');
        Route::post('{evidence}/start', 'start')->name('start');
        Route::patch('{evidence}', 'decide')->name('decide');
    });

    Route::controller(SkillShowModerationController::class)->prefix('skillshows')->name('skillshows.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::patch('{skillShow}', 'update')->name('update');
    });
});

Route::middleware(['guest', 'throttle:20,1'])->controller(StaffInvitationAcceptanceController::class)->group(function () {
    Route::get('staff-invitations/{token}', 'show')->where('token', '[A-Za-z0-9]{48}')->name('staff-invitations.show');
    Route::post('staff-invitations/{token}', 'store')->where('token', '[A-Za-z0-9]{48}')->name('staff-invitations.store');
});
