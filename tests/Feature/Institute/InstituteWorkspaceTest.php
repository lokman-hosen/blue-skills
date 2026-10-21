<?php

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Institute\Models\Cohort;
use App\Domain\Institute\Models\CohortMember;
use App\Domain\Institute\Models\Course;
use App\Domain\Institute\Models\TradeAssessment;
use App\Domain\Institute\Models\TrainingAttestation;
use App\Domain\Organization\Models\Organization;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, RolesAndPermissionsSeeder::class]);
    $this->institute = Organization::factory()->institute()->verified()->create();
    $this->cohort = Cohort::factory()->create([
        'course_id' => Course::factory()->create(['organization_id' => $this->institute->id]),
        'organization_id' => $this->institute->id,
    ]);
});

it('shows the institute dashboard tiles and graduates across cohorts', function () {
    $joined = CohortMember::factory()->accepted(sharesProgress: true)->create(['cohort_id' => $this->cohort->id]);
    CohortMember::factory()->create(['cohort_id' => $this->cohort->id]);
    TrainingAttestation::factory()->create(['cohort_member_id' => $joined->id]);
    TradeAssessment::factory()->create(['cohort_member_id' => $joined->id]);
    $assessor = orgMember($this->institute, OrganizationRole::Assessor);

    actingInOrg($assessor, $this->institute)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->component('org/dashboard')
            ->where('instituteCounts.graduates', 1)
            ->where('instituteCounts.rosterWaiting', 1)
            ->where('instituteCounts.attested', 1)
            ->where('instituteCounts.assessmentsPassed', 1)
            ->where('instituteCounts.sharingProgress', 1)
            ->has('recentCohorts', 1));

    $this->get(route('org.graduates.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('org/graduates')
            ->has('graduates.data', 1)
            ->where('graduates.data.0.cohortId', $this->cohort->id)
            ->where('graduates.data.0.attestation.status.value', 'record_attested')
            ->where('abilities.assess', true));
    $this->get(route('org.graduates.index', ['state' => 'waiting']))->assertInertia(fn (Assert $page) => $page->has('graduates.data', 1));
    $this->get(route('org.graduates.index', ['state' => 'all']))->assertInertia(fn (Assert $page) => $page->has('graduates.data', 2));

    $employer = Organization::factory()->employer()->verified()->create();
    actingInOrg(orgMember($employer, OrganizationRole::Owner), $employer)->get(route('org.graduates.index'))->assertForbidden();
});
