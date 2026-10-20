<?php

use App\Http\Controllers\Organization\OrganizationRegistrationController;
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
});
