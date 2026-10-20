<?php

use App\Http\Controllers\Organization\InvitationController;
use App\Http\Controllers\Organization\OrganizationRegistrationController;
use App\Http\Controllers\Organization\TeamController;
use App\Http\Controllers\Organization\VerificationEvidenceController;
use Illuminate\Support\Facades\Route;

// Organization self-registration and verification evidence (Module 1, D-036).
Route::middleware(['auth', 'verified', 'user.type:org'])->prefix('organizations')->name('organizations.')->group(function () {
    Route::get('create', [OrganizationRegistrationController::class, 'create'])->name('create');
    Route::post('/', [OrganizationRegistrationController::class, 'store'])->name('store');
    Route::get('verification', [VerificationEvidenceController::class, 'edit'])->name('verification.edit');
    Route::post('verification/evidence', [VerificationEvidenceController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('verification.evidence.store');
    Route::post('verification/resubmit', [VerificationEvidenceController::class, 'resubmit'])
        ->middleware('throttle:5,1')
        ->name('verification.resubmit');
});

// Team management for the current organization (Module 1C).
Route::middleware(['auth', 'verified', 'user.type:org'])->prefix('team')->name('team.')->controller(TeamController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('invitations', 'invite')->middleware('throttle:30,1')->name('invitations.store');
    Route::delete('invitations/{invitation}', 'revoke')->name('invitations.destroy');
    Route::patch('members/{member}', 'updateRole')->name('members.update');
    Route::delete('members/{member}', 'remove')->name('members.destroy');
});

Route::get('invitations/{token}', [InvitationController::class, 'show'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->middleware('throttle:30,1')
    ->name('invitations.show');

Route::middleware(['auth', 'verified', 'user.type:org'])->group(function () {
    Route::get('invitations', [InvitationController::class, 'index'])->name('invitations.index');
    Route::post('invitations/{invitation}/accept', [InvitationController::class, 'accept'])->name('invitations.accept');
});
