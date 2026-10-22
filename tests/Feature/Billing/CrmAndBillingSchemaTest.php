<?php

use App\Domain\Billing\Enums\CreditReason;
use App\Domain\Billing\Models\CreditLedgerEntry;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\OrganizationSubscription;
use App\Domain\Billing\Models\Plan;
use App\Domain\Crm\Models\Campaign;
use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\Models\LeadContactAttempt;
use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\Models\RecruitmentProject;
use App\Domain\Organization\Models\Organization;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->employer = Organization::factory()->employer()->verified()->create();
});

it('builds campaigns, leads, projects, subscriptions, invoices and the credit ledger', function () {
    $campaign = Campaign::factory()->create(['name' => 'Dhaka Job Fair 2026', 'code' => Campaign::newCode('Dhaka Job Fair 2026')]);
    $lead = Lead::factory()->forWorker()->create(['campaign_id' => $campaign->id]);
    LeadContactAttempt::factory()->create(['lead_id' => $lead->id]);

    $project = RecruitmentProject::factory()->create(['organization_id' => $this->employer->id]);
    JobPosting::factory()->create(['organization_id' => $this->employer->id, 'recruitment_project_id' => $project->id]);

    $subscription = OrganizationSubscription::factory()->create(['organization_id' => $this->employer->id]);
    $invoice = Invoice::factory()->create(['organization_id' => $this->employer->id]);
    $invoice->lines()->create(['description' => 'Employer Standard', 'quantity' => 1, 'unit_amount_minor' => 150_005, 'amount_minor' => 150_005]);
    $invoice->recalculate();
    CreditLedgerEntry::query()->create(['organization_id' => $this->employer->id, 'delta' => 10, 'reason' => CreditReason::PlanGrant]);
    CreditLedgerEntry::query()->create(['organization_id' => $this->employer->id, 'delta' => -1, 'reason' => CreditReason::JobPublished]);

    expect($campaign->code)->toBe('DHAKA-JOB-FAIR-2026')
        ->and(Campaign::newCode('Dhaka Job Fair 2026'))->toBe('DHAKA-JOB-FAIR-2026-2')
        ->and($lead->user?->lead?->id)->toBe($lead->id)
        ->and($lead->contactAttempts()->count())->toBe(1)
        ->and($project->reference)->toBe('RP-0001')
        ->and(RecruitmentProject::newReference($this->employer->id))->toBe('RP-0002')
        ->and($project->jobs()->count())->toBe(1)
        ->and($this->employer->activeSubscription?->id)->toBe($subscription->id)
        ->and($invoice->fresh()?->tax_minor)->toBe(7_500)
        ->and($invoice->fresh()?->total_minor)->toBe(157_505)
        ->and($this->employer->creditBalance())->toBe(9)
        ->and(Invoice::newNumber())->toBe('INV-'.now()->year.'-00001');
});

it('keeps the credit ledger append-only', function () {
    $entry = CreditLedgerEntry::query()->create(['organization_id' => $this->employer->id, 'delta' => 5, 'reason' => CreditReason::Adjustment]);

    expect(fn () => $entry->update(['delta' => 50]))->toThrow(LogicException::class)
        ->and(fn () => $entry->delete())->toThrow(LogicException::class);
});

it('gives CRM to CRM agents, billing to finance and projects to hiring roles', function () {
    $crm = staffUser(StaffRole::CrmAgent);
    $finance = staffUser(StaffRole::FinanceOfficer);
    $recruiter = orgMember($this->employer, OrganizationRole::Recruiter);
    $viewer = orgMember($this->employer, OrganizationRole::Viewer);
    $owner = orgMember($this->employer, OrganizationRole::Owner);
    $project = RecruitmentProject::factory()->create(['organization_id' => $this->employer->id]);
    $draft = Invoice::factory()->create(['organization_id' => $this->employer->id]);
    $issued = Invoice::factory()->issued()->create(['organization_id' => $this->employer->id]);

    expect(Gate::forUser($crm)->allows('viewAny', Lead::class))->toBeTrue()
        ->and(Gate::forUser($finance)->allows('viewAny', Lead::class))->toBeFalse()
        ->and(Gate::forUser($finance)->allows('create', Plan::class))->toBeTrue()
        ->and(Gate::forUser($crm)->allows('create', Plan::class))->toBeFalse()
        ->and(Gate::forUser($recruiter)->allows('update', $project))->toBeTrue()
        ->and(Gate::forUser($viewer)->allows('view', $project))->toBeTrue()
        ->and(Gate::forUser($viewer)->allows('update', $project))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('viewBilling', $this->employer))->toBeTrue()
        ->and(Gate::forUser($recruiter)->allows('viewBilling', $this->employer))->toBeFalse()
        ->and(Gate::forUser($owner)->allows('view', $issued))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('view', $draft))->toBeFalse()
        ->and(Gate::forUser($viewer)->allows('viewAnalytics', $this->employer))->toBeTrue();

    $other = Organization::factory()->employer()->verified()->create();
    expect(Gate::forUser(orgMember($other, OrganizationRole::Owner))->allows('view', $project))->toBeFalse();
});
