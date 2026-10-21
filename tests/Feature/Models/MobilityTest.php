<?php

use App\Domain\Applications\Models\Application;
use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Jobs\Enums\MandateStatus;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Mobility\Models\MobilityCaseEvent;
use App\Domain\Mobility\States\MobilityCase\Completed;
use App\Domain\Organization\Models\Organization;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->seed([CountrySeeder::class, RolesAndPermissionsSeeder::class]);
    $this->agency = Organization::factory()->agency()->verified()->create(['licence_expires_on' => now()->addYear(), 'licence_countries' => ['AE']]);
    $this->employer = Organization::factory()->employer()->verified()->create();
});

it('checks agency licences for expiry and destination', function () {
    expect($this->agency->hasValidLicence('AE'))->toBeTrue()
        ->and($this->agency->hasValidLicence('SA'))->toBeFalse();

    $this->agency->update(['licence_expires_on' => now()->subDay()]);
    expect($this->agency->hasValidLicence('AE'))->toBeFalse()
        ->and($this->employer->hasValidLicence())->toBeFalse();
});

it('uses a mandate only when verified, in date, in scope and licensed', function () {
    $mandate = JobMandate::factory()->create([
        'agency_organization_id' => $this->agency->id, 'employer_organization_id' => $this->employer->id,
        'status' => MandateStatus::Verified, 'destination_country_code' => 'AE', 'occupation_ids' => ['occ-1'],
        'valid_from' => now()->subMonth(), 'valid_until' => now()->addMonth(),
    ]);

    expect($mandate->isUsable())->toBeTrue()
        ->and($mandate->covers('occ-1', 'AE'))->toBeTrue()
        ->and($mandate->covers('occ-2', 'AE'))->toBeFalse()
        ->and($mandate->covers('occ-1', 'SA'))->toBeFalse();

    $mandate->update(['valid_until' => now()->subDay()]);
    expect($mandate->fresh()?->isUsable())->toBeFalse();
});

it('lets the managing organization run a case, the employer follow it and the worker respond', function () {
    $job = JobPosting::factory()->published()->create(['organization_id' => $this->agency->id, 'agency_organization_id' => $this->agency->id, 'employer_organization_id' => $this->employer->id]);
    $worker = makeWorker();
    $case = MobilityCase::factory()->create([
        'application_id' => Application::factory()->create(['job_id' => $job->id, 'worker_profile_id' => $worker->workerProfile->id]),
        'managing_organization_id' => $this->agency->id,
        'employer_organization_id' => $this->employer->id,
        'agency_organization_id' => $this->agency->id,
    ]);
    $officer = orgMember($this->agency, OrganizationRole::CaseOfficer);
    $employerHr = orgMember($this->employer, OrganizationRole::HrAdmin);

    expect(Gate::forUser($officer)->allows('manage', $case))->toBeTrue()
        ->and(Gate::forUser($employerHr)->allows('view', $case))->toBeTrue()
        ->and(Gate::forUser($employerHr)->allows('manage', $case))->toBeFalse()
        ->and(Gate::forUser(orgMember($this->agency, OrganizationRole::ComplianceReviewer))->allows('manage', $case))->toBeFalse()
        ->and(Gate::forUser($worker)->allows('respond', $case))->toBeTrue()
        ->and(Gate::forUser(makeWorker())->allows('view', $case))->toBeFalse();

    $case->status->transitionTo(Completed::class);
    expect(Gate::forUser($officer->fresh())->allows('manage', $case->fresh()))->toBeFalse();
});

it('never edits a case event', function () {
    $event = MobilityCaseEvent::query()->create(['mobility_case_id' => MobilityCase::factory()->create()->id, 'action' => 'opened']);
    $event->update(['note' => 'Changed']);
})->throws(LogicException::class);

it('lets only employer owners and HR admins confirm mandates', function () {
    $mandate = JobMandate::factory()->create(['agency_organization_id' => $this->agency->id, 'employer_organization_id' => $this->employer->id, 'status' => MandateStatus::AwaitingEmployer]);

    expect(Gate::forUser(orgMember($this->employer, OrganizationRole::Owner))->allows('confirm', $mandate))->toBeTrue()
        ->and(Gate::forUser(orgMember($this->employer, OrganizationRole::Recruiter))->allows('confirm', $mandate))->toBeFalse()
        ->and(Gate::forUser(orgMember($this->agency, OrganizationRole::AgencyOwner))->allows('confirm', $mandate))->toBeFalse();
});
