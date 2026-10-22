<?php

use App\Domain\Applications\Models\Application;
use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Jobs\Enums\MandateStatus;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Paused;
use App\Domain\Jobs\States\Job\Published;
use App\Domain\Mobility\Actions\OpenMobilityCase;
use App\Domain\Mobility\Enums\CaseDocumentStatus;
use App\Domain\Mobility\Enums\MobilityStageStatus;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Organization\Models\Organization;
use App\Domain\Verification\Enums\DocumentType;
use Database\Seeders\CountryRuleSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Artisan;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, CountryRuleSeeder::class, RolesAndPermissionsSeeder::class]);
    $this->employer = Organization::factory()->employer()->verified()->create(['display_name' => 'ABC Contracting']);
    $this->employerOwner = orgMember($this->employer, OrganizationRole::Owner);
    $this->agency = Organization::factory()->agency()->verified()->create([
        'display_name' => 'Northstar', 'licence_countries' => ['AE'], 'licence_expires_on' => now()->addYear(),
    ]);
    $this->agencyOwner = orgMember($this->agency, OrganizationRole::AgencyOwner);
    $this->officer = orgMember($this->agency, OrganizationRole::CaseOfficer);
    $this->mandate = JobMandate::factory()->create([
        'agency_organization_id' => $this->agency->id, 'employer_organization_id' => $this->employer->id,
        'status' => MandateStatus::Verified, 'destination_country_code' => 'AE', 'headcount' => 50,
        'valid_from' => now()->subMonth(), 'valid_until' => now()->addMonths(6),
    ]);
    $this->job = JobPosting::factory()->published()->create([
        'organization_id' => $this->agency->id, 'agency_organization_id' => $this->agency->id, 'job_mandate_id' => $this->mandate->id,
        'employer_organization_id' => $this->employer->id, 'destination_country_code' => 'AE', 'headcount' => 10,
    ]);
});

function workspaceCase(JobPosting $job): MobilityCase
{
    $application = Application::factory()->create(['job_id' => $job->id, 'worker_profile_id' => makeWorker()->workerProfile->id]);

    return app(OpenMobilityCase::class)->handle($application, test()->officer);
}

it('lists the agency\'s cases and what needs attention, and lets the employer follow them', function () {
    $first = workspaceCase($this->job);
    $second = workspaceCase($this->job);
    $first->documents()->where('type', DocumentType::Medical)->update(['status' => CaseDocumentStatus::Uploaded, 'shared_at' => now(), 'document_id' => null]);
    $second->stages()->where('key', $second->current_stage)->update(['due_on' => now()->subDays(2), 'status' => MobilityStageStatus::Blocked]);
    // Other organizations' cases never show.
    workspaceCase(JobPosting::factory()->published()->create(['destination_country_code' => 'AE']));

    actingInOrg($this->officer, $this->agency)->get(route('org.cases.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('org/cases/index')
            ->has('cases.data', 2)
            ->where('counts.open', 2)
            ->where('counts.documentsToCheck', 1)
            ->where('counts.overdue', 1)
            ->where('counts.onHold', 1)
            ->where('cases.data.0.viewOnly', false));

    $this->get(route('org.cases.index', ['attention' => 'overdue']))
        ->assertInertia(fn (Assert $page) => $page->has('cases.data', 1)->where('cases.data.0.id', $second->id)->where('cases.data.0.onHold', true));
    $this->get(route('org.cases.index', ['q' => $first->reference]))
        ->assertInertia(fn (Assert $page) => $page->has('cases.data', 1)->where('cases.data.0.id', $first->id));
    $this->get(route('org.cases.index', ['state' => 'closed']))
        ->assertInertia(fn (Assert $page) => $page->has('cases.data', 0));

    actingInOrg($this->employerOwner, $this->employer)->get(route('org.cases.index'))
        ->assertInertia(fn (Assert $page) => $page->has('cases.data', 2)->where('cases.data.0.viewOnly', true)->where('cases.data.0.managedBy', 'Northstar')
            // Only the agency checks shared files (D-081).
            ->where('counts.documentsToCheck', 0)
            ->where('counts.overdue', 1));

    $institute = Organization::factory()->institute()->verified()->create();
    actingInOrg(orgMember($institute, OrganizationRole::Trainer), $institute)->get(route('org.cases.index'))->assertForbidden();
});

it('lists candidates across the agency\'s jobs with filters', function () {
    $other = JobPosting::factory()->published()->create([
        'organization_id' => $this->agency->id, 'agency_organization_id' => $this->agency->id, 'job_mandate_id' => $this->mandate->id,
        'employer_organization_id' => $this->employer->id, 'destination_country_code' => 'AE', 'headcount' => 5,
    ]);
    $case = workspaceCase($this->job);
    Application::factory()->create(['job_id' => $other->id, 'worker_profile_id' => makeWorker()->workerProfile->id]);
    Application::factory()->create(['worker_profile_id' => makeWorker()->workerProfile->id]);

    actingInOrg($this->officer, $this->agency)->get(route('org.candidates.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('org/candidates/index')->has('candidates.data', 2)->has('jobs', 2));
    $this->get(route('org.candidates.index', ['job' => $this->job->public_code]))
        ->assertInertia(fn (Assert $page) => $page->has('candidates.data', 1)
            ->where('candidates.data.0.jobCode', $this->job->public_code)
            ->where('candidates.data.0.mobilityCaseId', $case->id));

    $institute = Organization::factory()->institute()->verified()->create();
    actingInOrg(orgMember($institute, OrganizationRole::Trainer), $institute)->get(route('org.candidates.index'))->assertForbidden();
});

it('shows compliance to agency owners and compliance reviewers only', function () {
    $this->agency->update(['licence_expires_on' => now()->addDays(20)]);

    actingInOrg($this->agencyOwner, $this->agency)->get(route('org.compliance'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('org/compliance')
            ->where('licence.valid', true)
            ->where('licence.expiringSoon', true)
            ->where('mandates.0.placesLeft', 40)
            ->where('mandates.0.liveJobs', 1)
            ->has('uncoveredJobs', 0)
            ->where('organizationAbilities.reviewCompliance', true));
    $this->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page->where('licenceAlert.state', 'expiring'));

    actingInOrg(orgMember($this->agency, OrganizationRole::ComplianceReviewer), $this->agency)->get(route('org.compliance'))->assertOk();
    actingInOrg($this->officer, $this->agency)->get(route('org.compliance'))->assertForbidden();
    // Case officers don't see links they can't open (D-081).
    $this->get(route('org.cases.index'))->assertInertia(fn (Assert $page) => $page
        ->where('organizationAbilities.reviewCompliance', false)
        ->where('organizationAbilities.viewMandates', false));
    $this->get(route('org.licence.edit'))->assertForbidden();
    actingInOrg($this->employerOwner, $this->employer)->get(route('org.compliance'))->assertForbidden();
});

it('expires lapsed mandates and pauses the live jobs they backed', function () {
    $this->mandate->update(['valid_until' => now()->subDay()]);
    $direct = JobPosting::factory()->published()->create(['organization_id' => $this->employer->id, 'employer_organization_id' => $this->employer->id]);

    Artisan::call('jobs:pause-lapsed-mandates');

    expect($this->mandate->fresh()?->status)->toBe(MandateStatus::Expired)
        ->and($this->job->fresh()?->status->equals(Paused::class))->toBeTrue()
        ->and($direct->fresh()?->status->equals(Published::class))->toBeTrue()
        ->and($this->agencyOwner->notifications()->pluck('type')->all())->toContain('job_paused_mandate');

    actingInOrg($this->agencyOwner, $this->agency)->get(route('org.compliance'))
        ->assertInertia(fn (Assert $page) => $page->has('uncoveredJobs', 1)->where('uncoveredJobs.0.mandateReference', $this->mandate->reference));

    // The Truth Card still says the job was reviewed, and that the mandate isn't valid.
    $this->get(route('org.jobs.show', $this->job))->assertInertia(fn (Assert $page) => $page
        ->where('job.checks', fn ($checks) => collect($checks)->pluck('key')->contains('job_reviewed')
            && collect($checks)->pluck('key')->contains('mandate_not_valid')));

    // It can't go live again until a usable mandate backs it.
    $this->post(route('org.jobs.availability', [$this->job, 'resume']))->assertSessionHasErrors('job');
    $this->mandate->refresh()->update(['status' => MandateStatus::Verified, 'valid_until' => now()->addMonth()]);
    $this->post(route('org.jobs.availability', [$this->job, 'resume']))->assertRedirect();
    expect($this->job->fresh()?->status->equals(Published::class))->toBeTrue();
});

it('pauses live agency jobs when the licence stops covering the destination', function () {
    $this->agency->update(['licence_countries' => ['SA']]);

    Artisan::call('jobs:pause-lapsed-mandates');

    expect($this->job->fresh()?->status->equals(Paused::class))->toBeTrue()
        ->and($this->mandate->fresh()?->status)->toBe(MandateStatus::Verified);
});

it('shows mobility tiles on the dashboard and the employer and mandate on job orders', function () {
    workspaceCase($this->job);

    actingInOrg($this->officer, $this->agency)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('caseCounts.open', 1)->where('licenceAlert', null));
    $this->get(route('org.jobs.index'))
        ->assertInertia(fn (Assert $page) => $page->where('jobs.data.0.employer', 'ABC Contracting')->where('jobs.data.0.mandateReference', $this->mandate->reference));
});
