<?php

use App\Domain\Taxonomy\Actions\SaveTaxonomyItem;
use App\Http\Controllers\Admin\AppealDecisionController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BillingController;
use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\ConfigController;
use App\Http\Controllers\Admin\EvidenceReviewController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\JobReviewController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\MandateReviewController;
use App\Http\Controllers\Admin\OrganizationReviewController;
use App\Http\Controllers\Admin\PilotInviteController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\SkillShowModerationController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StaffInvitationAcceptanceController;
use App\Http\Controllers\Admin\TaxonomyController;
use App\Http\Controllers\Admin\TrustCaseController;
use App\Http\Controllers\Admin\TrustReportController;
use Illuminate\Support\Facades\Route;

// Staff management (Module 1D). Staff screens need confirmed 2FA (D-035).
Route::middleware(['auth', 'verified', 'user.type:staff', 'staff.2fa'])->prefix('admin')->name('admin.')->group(function () {
    Route::controller(StaffController::class)->prefix('staff')->name('staff.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('invitations', 'invite')->middleware('throttle:30,1')->name('invitations.store');
        Route::delete('invitations/{invitation}', 'revoke')->name('invitations.destroy');
        Route::patch('members/{member}', 'updateRole')->name('members.update');
        Route::delete('members/{member}', 'deactivate')->name('members.destroy');
        // Lost phone: clear two-factor and passkeys so they set it up again (D-111).
        Route::post('members/{member}/two-factor/reset', 'resetTwoFactor')->middleware('throttle:10,1')->name('members.two-factor.reset');
    });

    // Roles & permissions: the Superuser decides what each role may do (D-112).
    Route::controller(RolePermissionController::class)->prefix('roles')->name('roles.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->middleware('throttle:30,1')->name('store');
        Route::get('{role}', 'edit')->name('edit');
        Route::patch('{role}/details', 'details')->middleware('throttle:30,1')->name('details');
        Route::delete('{role}', 'destroy')->middleware('throttle:30,1')->name('destroy');
        Route::put('{role}', 'update')->middleware('throttle:30,1')->name('update');
        Route::post('{role}/reset', 'reset')->middleware('throttle:30,1')->name('reset');
    });

    // Organization registration review (Module 3).
    Route::controller(OrganizationReviewController::class)->prefix('organizations')->name('organizations.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('reviews/{review}', 'show')->name('show');
        Route::patch('reviews/{review}', 'decide')->name('decide');
    });

    // Pilot invite codes for selected partners (Module 10, D-108).
    Route::controller(PilotInviteController::class)->prefix('pilot')->name('pilot.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->middleware('throttle:30,1')->name('store');
        Route::post('{invite}/revoke', 'revoke')->name('revoke');
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
    // Worker CRM (Module 9, spec §9, D-095).
    Route::controller(LeadController::class)->prefix('crm')->name('crm.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('leads', 'store')->middleware('throttle:60,1')->name('leads.store');
        Route::get('leads/{lead}', 'show')->name('leads.show');
        Route::put('leads/{lead}', 'update')->name('leads.update');
        Route::post('leads/{lead}/attempts', 'attempt')->middleware('throttle:60,1')->name('leads.attempts.store');
    });

    // Marketing campaigns and their funnels (Module 9, spec §9).
    Route::controller(CampaignController::class)->prefix('marketing')->name('marketing.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('{campaign}', 'show')->name('show');
        Route::put('{campaign}', 'update')->name('update');
        Route::post('{campaign}/{action}', 'status')->whereIn('action', ['pause', 'resume', 'end'])->name('status');
        Route::get('{campaign}/qr', 'qr')->name('qr');
    });

    // Platform billing for Finance Officers (Module 9, spec §11, D-095).
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/', [BillingController::class, 'index'])->name('index');
        Route::get('organizations/{organization}', [BillingController::class, 'show'])->name('organizations.show');
        Route::post('organizations/{organization}/plan', [BillingController::class, 'assignPlan'])->name('organizations.plan');
        Route::post('organizations/{organization}/credits', [BillingController::class, 'adjustCredits'])->name('organizations.credits');
        Route::post('organizations/{organization}/invoices', [BillingController::class, 'createInvoice'])->name('organizations.invoices.store');
        Route::get('plans', [PlanController::class, 'index'])->name('plans.index');
        Route::post('plans', [PlanController::class, 'store'])->name('plans.store');
        Route::put('plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
        Route::controller(InvoiceController::class)->prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('{invoice}', 'show')->name('show');
            Route::post('{invoice}/lines', 'addLine')->name('lines.store');
            Route::delete('{invoice}/lines/{line}', 'removeLine')->name('lines.destroy');
            Route::post('{invoice}/issue', 'issue')->name('issue');
            Route::post('{invoice}/pay', 'pay')->name('pay');
            Route::post('{invoice}/void', 'void')->name('void');
        });
    });

    // Trust: reports, cases, suspensions and appeals (Module 8, spec §10).
    Route::prefix('trust')->name('trust.')->group(function () {
        Route::get('reports', [TrustReportController::class, 'index'])->name('reports.index');
        Route::post('reports/{report}/dismiss', [TrustReportController::class, 'dismiss'])->name('reports.dismiss');
        Route::controller(TrustCaseController::class)->prefix('cases')->name('cases.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::get('{case}', 'show')->name('show');
            Route::put('{case}', 'update')->name('update');
            Route::post('{case}/notes', 'note')->middleware('throttle:30,1')->name('notes.store');
            Route::post('{case}/actions/{action}', 'action')->whereIn('action', ['suspend', 'restore'])->name('actions.store');
            Route::post('{case}/close', 'close')->name('close');
        });
        Route::post('appeals/{appeal}/{decision}', AppealDecisionController::class)->whereIn('decision', ['uphold', 'overturn'])->name('appeals.decide');
    });

    Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');

    // Configuration and taxonomy (Module 8, D-093).
    Route::controller(ConfigController::class)->prefix('config')->name('config.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('countries/{country}', 'show')->name('countries.show');
        Route::put('countries/{country}', 'update')->name('countries.update');
        Route::put('countries/{country}/rules', 'rules')->name('countries.rules');
        Route::put('languages/{language}', 'language')->name('languages.update');
    });
    Route::controller(TaxonomyController::class)->prefix('taxonomy')->name('taxonomy.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('occupations/{occupation}', 'show')->name('occupations.show');
        Route::post('/', 'store')->name('store');
        Route::put('{type}/{id}', 'update')->whereIn('type', SaveTaxonomyItem::TYPES)->name('update');
        Route::delete('{type}/{id}', 'destroy')->whereIn('type', SaveTaxonomyItem::TYPES)->name('destroy');
    });

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
