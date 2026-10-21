<?php

use App\Domain\Jobs\Actions\ChangeJobAvailability;
use App\Domain\Jobs\Support\JobWizard;
use App\Domain\Mobility\Actions\ChangeCaseStage;
use App\Domain\Mobility\Actions\DecideCaseDocument;
use App\Http\Controllers\Organization\ApplicantController;
use App\Http\Controllers\Organization\CandidateController;
use App\Http\Controllers\Organization\CaseDocumentController;
use App\Http\Controllers\Organization\CohortController;
use App\Http\Controllers\Organization\ComplianceController;
use App\Http\Controllers\Organization\CourseController;
use App\Http\Controllers\Organization\GraduateController;
use App\Http\Controllers\Organization\GraduateRecordController;
use App\Http\Controllers\Organization\InstitutePoolController;
use App\Http\Controllers\Organization\InvitationController;
use App\Http\Controllers\Organization\JobController;
use App\Http\Controllers\Organization\LicenceController;
use App\Http\Controllers\Organization\MandateController;
use App\Http\Controllers\Organization\MessageController;
use App\Http\Controllers\Organization\MobilityCaseController;
use App\Http\Controllers\Organization\OrganizationRegistrationController;
use App\Http\Controllers\Organization\PlacementController;
use App\Http\Controllers\Organization\ShortlistController;
use App\Http\Controllers\Organization\TalentController;
use App\Http\Controllers\Organization\TalentPoolController;
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
    Route::get('candidates', [CandidateController::class, 'index'])->name('candidates.index');
    Route::get('talent/{profile:reference}', [TalentController::class, 'show'])->name('talent.show');
    Route::post('talent/{profile:reference}/invitations', [TalentController::class, 'invite'])->middleware('throttle:30,1')->name('talent.invite');

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

// Agency licence and mandates (Module 6, D-074).
Route::middleware(['auth', 'verified', 'user.type:org'])->prefix('org')->name('org.')->group(function () {
    Route::get('licence', [LicenceController::class, 'edit'])->name('licence.edit');
    Route::put('licence', [LicenceController::class, 'update'])->name('licence.update');
    Route::get('compliance', [ComplianceController::class, 'show'])->name('compliance');

    Route::controller(MandateController::class)->prefix('mandates')->name('mandates.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('new', 'create')->name('create');
        Route::post('/', 'store')->middleware('throttle:10,1')->name('store');
        Route::get('{mandate}', 'show')->name('show');
        Route::post('{mandate}/{answer}', 'respond')->whereIn('answer', ['confirm', 'decline'])->name('respond');
        Route::post('{mandate}/evidence', 'evidence')->middleware('throttle:20,1')->name('evidence');
    });
});

// Mobility cases (Module 6, product rule 8).
Route::middleware(['auth', 'verified', 'user.type:org'])->prefix('org/cases')->name('org.cases.')->controller(MobilityCaseController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('{mobilityCase}', 'show')->name('show');
    Route::post('{mobilityCase}/close', 'close')->name('close');
    Route::scopeBindings()->group(function () {
        Route::post('{mobilityCase}/stages/{stage}/{action}', 'stage')->whereIn('action', ChangeCaseStage::ACTIONS)->name('stages.change');
        Route::put('{mobilityCase}/stages/{stage}', 'nextAction')->name('stages.update');
    });
});

// Case documents (Module 6, product rule 6, D-079).
Route::middleware(['auth', 'verified', 'user.type:org'])->prefix('org/cases/{mobilityCase}/documents')->name('org.cases.documents.')->controller(CaseDocumentController::class)->scopeBindings()->group(function () {
    Route::post('/', 'store')->name('store');
    Route::post('{document}/file', 'upload')->middleware('throttle:20,1')->name('upload');
    Route::post('{document}/{decision}', 'decide')->whereIn('decision', DecideCaseDocument::DECISIONS)->name('decide');
});

// Institutes: courses, cohorts and graduate invitations (Module 7, D-082).
Route::middleware(['auth', 'verified', 'user.type:org'])->prefix('org')->name('org.')->group(function () {
    Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
    Route::post('courses', [CourseController::class, 'store'])->name('courses.store');
    Route::put('courses/{course}', [CourseController::class, 'update'])->name('courses.update');
    Route::post('courses/{course}/cohorts', [CohortController::class, 'store'])->name('cohorts.store');

    Route::controller(CohortController::class)->prefix('cohorts')->name('cohorts.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('{cohort}', 'show')->name('show');
        Route::put('{cohort}', 'update')->name('update');
        Route::post('{cohort}/status/{status}', 'status')->whereIn('status', ['open', 'closed'])->name('status');
        Route::get('{cohort}/qr', 'qr')->name('qr');
        Route::post('{cohort}/roster', 'roster')->middleware('throttle:20,1')->name('roster');
        Route::delete('{cohort}/members/{member}', 'removeMember')->scopeBindings()->name('members.destroy');
    });

    // Talent pools (spec §8, D-082): institutes keep them, verified employers and agencies browse.
    Route::controller(TalentPoolController::class)->prefix('pools')->name('pools.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('{pool}', 'show')->name('show');
        Route::put('{pool}', 'update')->name('update');
        Route::post('{pool}/{state}', 'publish')->whereIn('state', ['publish', 'unpublish'])->name('publish');
        Route::post('{pool}/members', 'addMembers')->name('members.store');
        Route::delete('{pool}/members/{profile}', 'removeMember')->name('members.destroy');
    });
    Route::get('institute-pools', [InstitutePoolController::class, 'index'])->name('institute-pools.index');
    Route::get('institute-pools/{pool}', [InstitutePoolController::class, 'show'])->name('institute-pools.show');
    Route::get('placements', [PlacementController::class, 'index'])->name('placements.index');
    Route::get('graduates', [GraduateController::class, 'index'])->name('graduates.index');

    // Attestations and assessments for graduates who accepted (product rules 9 and 11).
    Route::controller(GraduateRecordController::class)->group(function () {
        Route::post('cohorts/{cohort}/members/{member}/attestation', 'attest')->scopeBindings()->name('cohorts.members.attest');
        Route::post('cohorts/{cohort}/members/{member}/assessments', 'assess')->scopeBindings()->name('cohorts.members.assess');
        Route::post('attestations/{attestation}/withdraw', 'withdrawAttestation')->name('attestations.withdraw');
        Route::post('assessments/{assessment}/withdraw', 'withdrawAssessment')->name('assessments.withdraw');
    });
});
