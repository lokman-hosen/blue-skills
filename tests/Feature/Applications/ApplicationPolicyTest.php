<?php

use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\Shortlist;
use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Organization\Models\Organization;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->seed([CountrySeeder::class, RolesAndPermissionsSeeder::class]);
    $this->employer = Organization::factory()->employer()->verified()->create();
    $this->worker = makeWorker();
    $this->application = Application::factory()->create([
        'job_id' => JobPosting::factory()->create(['organization_id' => $this->employer->id, 'employer_organization_id' => $this->employer->id]),
        'worker_profile_id' => $this->worker->workerProfile->id,
    ]);
});

it('lets the worker see and answer only their own application', function () {
    expect(Gate::forUser($this->worker)->allows('view', $this->application))->toBeTrue()
        ->and(Gate::forUser($this->worker)->allows('respond', $this->application))->toBeTrue()
        ->and(Gate::forUser($this->worker)->allows('manage', $this->application))->toBeFalse();

    $other = makeWorker();
    expect(Gate::forUser($other)->allows('view', $this->application))->toBeFalse()
        ->and(Gate::forUser($other)->allows('respond', $this->application))->toBeFalse();
});

it('scopes organization access by role', function (OrganizationRole $role, bool $view, bool $manage, bool $interview) {
    $member = orgMember($this->employer, $role);
    $gate = Gate::forUser($member);

    expect($gate->allows('view', $this->application))->toBe($view)
        ->and($gate->allows('manage', $this->application))->toBe($manage)
        ->and($gate->allows('scheduleInterview', $this->application))->toBe($interview)
        ->and($gate->allows('respond', $this->application))->toBeFalse();
})->with([
    'owner' => [OrganizationRole::Owner, true, true, true],
    'recruiter' => [OrganizationRole::Recruiter, true, true, true],
    'hiring manager' => [OrganizationRole::HiringManager, true, false, true],
    'viewer' => [OrganizationRole::Viewer, true, false, false],
]);

it('keeps other organizations out of applications and shortlists', function () {
    $outsider = orgMember(Organization::factory()->employer()->verified()->create(), OrganizationRole::Owner);
    $shortlist = Shortlist::factory()->create(['organization_id' => $this->employer->id]);

    expect(Gate::forUser($outsider)->allows('view', $this->application))->toBeFalse()
        ->and(Gate::forUser($outsider)->allows('manage', $shortlist))->toBeFalse()
        ->and(Gate::forUser(orgMember($this->employer, OrganizationRole::HiringManager))->allows('manage', $shortlist))->toBeTrue()
        ->and(Gate::forUser(orgMember($this->employer, OrganizationRole::Viewer))->allows('manage', $shortlist))->toBeFalse();
});
