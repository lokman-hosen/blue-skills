<?php

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Institute\Models\Cohort;
use App\Domain\Institute\Models\CohortMember;
use App\Domain\Institute\Models\Course;
use App\Domain\Institute\Models\TalentPool;
use App\Domain\Institute\Models\TradeAssessment;
use App\Domain\Institute\Models\TrainingAttestation;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\States\Verification\Pending;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->institute = Organization::factory()->institute()->verified()->create();
    $this->cohort = Cohort::factory()->for(Course::factory()->for($this->institute))->create(['organization_id' => $this->institute->id]);
});

it('builds courses, cohorts, graduates, attestations, assessments and pools', function () {
    $member = CohortMember::factory()->accepted()->create(['cohort_id' => $this->cohort->id]);
    $attestation = TrainingAttestation::factory()->create(['cohort_member_id' => $member->id]);
    $assessment = TradeAssessment::factory()->create(['cohort_member_id' => $member->id]);
    $pool = TalentPool::factory()->published()->create(['organization_id' => $this->institute->id]);
    $pool->workers()->attach($member->worker_profile_id);

    expect($this->cohort->invite_code)->toMatch('/^[2-9A-HJKMNP-Z]{8}$/')
        ->and($this->institute->cohorts()->count())->toBe(1)
        ->and($attestation->organization_id)->toBe($this->institute->id)
        ->and($assessment->worker_profile_id)->toBe($member->worker_profile_id)
        ->and($member->workerProfile?->cohortMemberships()->count())->toBe(1)
        ->and($pool->workers()->count())->toBe(1);
});

it('lets institute roles do only their own part', function () {
    $trainer = orgMember($this->institute, OrganizationRole::Trainer);
    $assessor = orgMember($this->institute, OrganizationRole::Assessor);
    $placement = orgMember($this->institute, OrganizationRole::PlacementOfficer);
    $accepted = CohortMember::factory()->accepted(sharesProgress: true)->create(['cohort_id' => $this->cohort->id]);
    $invited = CohortMember::factory()->create(['cohort_id' => $this->cohort->id]);

    expect(Gate::forUser($trainer)->allows('attest', $accepted))->toBeTrue()
        ->and(Gate::forUser($trainer)->allows('attest', $invited))->toBeFalse()
        ->and(Gate::forUser($placement)->allows('attest', $accepted))->toBeFalse()
        ->and(Gate::forUser($assessor)->allows('assess', $accepted))->toBeTrue()
        ->and(Gate::forUser($placement)->allows('invite', $this->cohort))->toBeTrue()
        ->and(Gate::forUser($placement)->allows('viewProgress', $accepted))->toBeTrue()
        ->and(Gate::forUser($trainer)->allows('viewProgress', $accepted))->toBeFalse()
        ->and(Gate::forUser($placement)->allows('viewAny', [TalentPool::class, $this->institute]))->toBeTrue()
        ->and(Gate::forUser($trainer)->allows('viewAny', [TalentPool::class, $this->institute]))->toBeFalse();

    // An unverified institute can't attest yet; other organizations see nothing.
    $this->institute->update(['verification_status' => Pending::class]);
    $employer = Organization::factory()->employer()->verified()->create();
    expect(Gate::forUser($trainer)->allows('attest', $accepted->fresh()))->toBeFalse()
        ->and(Gate::forUser(orgMember($employer, OrganizationRole::Owner))->allows('view', $this->cohort))->toBeFalse();
});
