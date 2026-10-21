<?php

use App\Domain\Mobility\Actions\AnswerCaseContract;
use App\Http\Controllers\Worker\ApplicationController;
use App\Http\Controllers\Worker\ApplyController;
use App\Http\Controllers\Worker\Auth\PhoneSignInController;
use App\Http\Controllers\Worker\BasicsController;
use App\Http\Controllers\Worker\CaseDocumentController;
use App\Http\Controllers\Worker\DocumentController;
use App\Http\Controllers\Worker\MessageController;
use App\Http\Controllers\Worker\MobilityCaseController;
use App\Http\Controllers\Worker\PassportController;
use App\Http\Controllers\Worker\PrivacyController;
use App\Http\Controllers\Worker\SkillShowController;
use App\Http\Controllers\Worker\VoiceController;
use Illuminate\Support\Facades\Route;

// Worker sign-in / sign-up by phone (Module 1).
Route::middleware('guest')->prefix('join')->name('worker.')->controller(PhoneSignInController::class)->group(function () {
    Route::get('/', 'language')->name('join');
    Route::get('phone', 'phone')->name('phone');
    Route::post('phone', 'sendCode')->middleware('throttle:10,1')->name('phone.store');
    Route::get('verify', 'verify')->name('verify');
    Route::post('verify', 'confirmCode')->middleware('throttle:10,1')->name('verify.store');
    Route::post('resend', 'resend')->middleware('throttle:5,1')->name('resend');
    Route::get('details', 'details')->name('details');
    Route::post('details', 'register')->middleware('throttle:10,1')->name('details.store');
});

// Skill Passport: onboarding, sections and Voice-to-CV (Module 2).
Route::middleware(['auth', 'verified', 'user.type:worker'])->prefix('passport')->name('worker.')->group(function () {
    Route::get('basics', [BasicsController::class, 'edit'])->name('basics.edit');
    Route::put('basics', [BasicsController::class, 'update'])->name('basics.update');

    Route::controller(PassportController::class)->name('passport.')->group(function () {
        Route::get('/', 'show')->name('show');
        Route::get('edit/{section}', 'edit')->whereIn('section', PassportController::SECTIONS)->name('edit');
        Route::put('about', 'updateAbout')->name('about.update');
        Route::post('experiences', 'storeExperience')->name('experiences.store');
        Route::put('experiences/{experience}', 'updateExperience')->name('experiences.update');
        Route::delete('experiences/{experience}', 'destroyExperience')->name('experiences.destroy');
        Route::put('skills', 'updateSkills')->name('skills.update');
        Route::put('languages', 'updateLanguages')->name('languages.update');
        Route::put('preferences', 'updatePreferences')->name('preferences.update');
        Route::post('photo', 'updatePhoto')->middleware('throttle:10,1')->name('photo.update');
        Route::delete('photo', 'destroyPhoto')->name('photo.destroy');
        Route::post('evidence', 'storeCredential')->middleware('throttle:20,1')->name('evidence.store');
        Route::delete('evidence/{evidence}', 'destroyCredential')->name('evidence.destroy');
    });

    Route::controller(PrivacyController::class)->prefix('privacy')->name('privacy.')->group(function () {
        Route::get('/', 'show')->name('show');
        Route::put('visibility', 'updateVisibility')->name('visibility.update');
        Route::post('delete-code', 'sendDeletionCode')->middleware('throttle:5,1')->name('deletion-code.store');
        Route::delete('account', 'destroy')->middleware('throttle:10,1')->name('account.destroy');
    });

    Route::controller(DocumentController::class)->prefix('documents')->name('documents.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->middleware('throttle:10,1')->name('store');
        Route::delete('{document}', 'destroy')->name('destroy');
    });

    Route::controller(SkillShowController::class)->prefix('skillshow')->name('skillshow.')->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::post('/', 'store')->middleware('throttle:10,1')->name('store');
        Route::put('{skillShow}', 'update')->name('update');
        Route::delete('{skillShow}', 'destroy')->name('destroy');
    });

    Route::controller(VoiceController::class)->prefix('voice')->name('voice.')->group(function () {
        Route::get('/', 'show')->name('show');
        Route::get('review', 'review')->name('review');
        Route::post('apply', 'apply')->name('apply');
        Route::post('{question}', 'submit')->middleware('throttle:30,1')->name('submit');
        Route::post('{question}/confirm', 'confirm')->name('confirm');
    });
});

// Applying to a job (Module 4).
Route::middleware(['auth', 'verified', 'user.type:worker'])->prefix('jobs/{job}/apply')->name('worker.apply.')->controller(ApplyController::class)->group(function () {
    Route::get('/', 'create')->name('create');
    Route::post('/', 'store')->middleware('throttle:10,1')->name('store');
});

// My applications (Module 4).
Route::middleware(['auth', 'verified', 'user.type:worker'])->prefix('applications')->name('worker.applications.')->controller(ApplicationController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('invitations/{invitation}/decline', 'declineInvitation')->name('invitations.decline');
    Route::get('{application}', 'show')->name('show');
    Route::post('{application}/withdraw', 'withdraw')->name('withdraw');
    Route::post('{application}/offer/{decision}', 'respondToOffer')->whereIn('decision', ['accept', 'decline'])->name('offer');
    Route::delete('{application}/items/{item}', 'revokeItem')->name('items.destroy');
    Route::post('{application}/job-change-seen', 'acknowledgeJobChange')->name('job-change-seen');
    Route::get('{application}/messages', [MessageController::class, 'open'])->name('messages');
});

// Messages (Module 5).
Route::middleware(['auth', 'verified', 'user.type:worker'])->prefix('messages')->name('worker.messages.')->controller(MessageController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('{conversation}', 'show')->name('show');
    Route::post('{conversation}', 'store')->middleware('throttle:30,1')->name('store');
});

// Mobility case after an accepted offer (Module 6, product rule 8).
Route::middleware(['auth', 'verified', 'user.type:worker'])->prefix('cases')->name('worker.cases.')->controller(MobilityCaseController::class)->group(function () {
    Route::get('{mobilityCase}', 'show')->name('show');
});

// Case documents: per-item sharing and the contract (product rule 6, D-079).
Route::middleware(['auth', 'verified', 'user.type:worker'])->prefix('cases/{mobilityCase}/documents')->name('worker.cases.documents.')->controller(CaseDocumentController::class)->scopeBindings()->group(function () {
    Route::post('{document}', 'share')->middleware('throttle:20,1')->name('share');
    Route::put('{document}/visibility', 'visibility')->name('visibility');
    Route::delete('{document}', 'revoke')->name('revoke');
    Route::post('{document}/contract/{answer}', 'contract')->whereIn('answer', AnswerCaseContract::ANSWERS)->name('contract');
});
