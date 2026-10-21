<?php

use App\Domain\Jobs\Actions\ChangeJobAvailability;
use App\Domain\Jobs\Support\JobWizard;
use App\Http\Controllers\Organization\ApplicantController;
use App\Http\Controllers\Organization\InvitationController;
use App\Http\Controllers\Organization\JobController;
use App\Http\Controllers\Organization\MessageController;
use App\Http\Controllers\Organization\OrganizationRegistrationController;
use App\Http\Controllers\Organization\ShortlistController;
use App\Http\Controllers\Organization\TalentController;
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

// The organization's jobs and the Post a Job wizard (Module 3, employers only: D-053).
Route::middleware(['auth', 'verified', 'user.type:org'])->prefix('org/jobs')->name('org.jobs.')->controller(JobController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('new', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('{job}', 'show')->name('show');
    Route::get('{job}/applicants', [ApplicantController::class, 'index'])->name('applicants');
    Route::get('{job}/edit/{step}', 'edit')->whereIn('step', [...JobWizard::STEPS, 'preview'])->name('edit');
    Route::put('{job}/steps/{step}', 'update')->whereIn('step', JobWizard::STEPS)->name('update');
    Route::post('{job}/submit', 'submit')->middleware('throttle:20,1')->name('submit');
    Route::post('{job}/{action}', 'availability')->whereIn('action', ChangeJobAvailability::ACTIONS)->name('availability');
});

// Applicants, pipeline, interviews and offers (Module 4).
Route::middleware(['auth', 'verified', 'user.type:org'])->prefix('org')->name('org.')->controller(ApplicantController::class)->group(function () {
    Route::post('applicants/move', 'move')->middleware('throttle:60,1')->name('applicants.move');
    Route::get('applicants/{application}', 'show')->name('applicants.show');
    Route::post('applicants/{application}/reject', 'reject')->name('applicants.reject');
    Route::put('applicants/{application}/assignee', 'assign')->name('applicants.assign');
    Route::post('applicants/{application}/interviews', 'scheduleInterview')->name('applicants.interviews.store');
    Route::put('interviews/{interview}', 'interviewOutcome')->name('interviews.update');
    Route::post('applicants/{application}/offers', 'sendOffer')->name('applicants.offers.store');
});

// Talent search and shortlists (Module 4).
Route::middleware(['auth', 'verified', 'user.type:org'])->prefix('org')->name('org.')->group(function () {
    Route::get('talent', [TalentController::class, 'index'])->name('talent.index');
    Route::get('talent/{profile:reference}', [TalentController::class, 'show'])->name('talent.show');

    Route::controller(ShortlistController::class)->prefix('shortlists')->name('shortlists.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->middleware('throttle:30,1')->name('store');
        Route::get('{shortlist}', 'show')->name('show');
        Route::delete('{shortlist}', 'destroy')->name('destroy');
        Route::post('{shortlist}/entries', 'addEntry')->middleware('throttle:60,1')->name('entries.store');
        Route::delete('{shortlist}/entries/{entry}', 'removeEntry')->name('entries.destroy');
    });
});

// Inbox (Module 5).
Route::middleware(['auth', 'verified', 'user.type:org'])->prefix('org')->name('org.')->controller(MessageController::class)->group(function () {
    Route::get('messages', 'index')->name('messages.index');
    Route::get('messages/{conversation}', 'show')->name('messages.show');
    Route::post('messages/{conversation}', 'store')->middleware('throttle:60,1')->name('messages.store');
    Route::get('applicants/{application}/messages', 'open')->name('applicants.messages');
});
