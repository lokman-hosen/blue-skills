<?php

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Institute\Enums\IssuedRecordStatus;
use App\Domain\Institute\Models\Cohort;
use App\Domain\Institute\Models\CohortMember;
use App\Domain\Institute\Models\Course;
use App\Domain\Institute\Models\TradeAssessment;
use App\Domain\Institute\Models\TrainingAttestation;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\States\Verification\Pending;
use App\Domain\Verification\Enums\EvidenceType;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Verification\States\Evidence\IssuerVerified;
use App\Domain\Verification\States\Evidence\Rejected;
use App\Domain\Worker\Data\EvidenceSummaryData;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, RolesAndPermissionsSeeder::class]);
    $this->institute = Organization::factory()->institute()->verified()->create(['display_name' => 'Meghna TTC']);
    $this->trainer = orgMember($this->institute, OrganizationRole::Trainer);
    $this->assessor = orgMember($this->institute, OrganizationRole::Assessor);
    $this->cohort = Cohort::factory()->create([
        'course_id' => Course::factory()->create(['organization_id' => $this->institute->id, 'title' => 'Electrical Installation']),
        'organization_id' => $this->institute->id,
    ]);
    $this->worker = makeWorker();
    $this->member = CohortMember::factory()->accepted($this->worker->workerProfile)->create(['cohort_id' => $this->cohort->id]);
});

it('attests training as its own issuer-verified evidence on the Skill Passport', function () {
    actingInOrg($this->trainer, $this->institute)
        ->post(route('org.cohorts.members.attest', [$this->cohort, $this->member]), ['completed_on' => now()->addDay()->toDateString()])
        ->assertSessionHasErrors('completed_on');
    $this->post(route('org.cohorts.members.attest', [$this->cohort, $this->member]), ['completed_on' => '2026-06-30'])
        ->assertRedirect(route('org.cohorts.show', $this->cohort));

    $attestation = TrainingAttestation::query()->sole();
    $evidence = $attestation->evidence;
    expect($attestation->course_title)->toBe('Electrical Installation')
        ->and($evidence?->type)->toBe(EvidenceType::Training)
        ->and($evidence?->status->equals(IssuerVerified::class))->toBeTrue()
        ->and($evidence?->verified_by)->toBe($this->trainer->id)
        ->and($this->worker->notifications()->pluck('type')->all())->toContain('institute_record');

    // One attestation per cohort place.
    $this->post(route('org.cohorts.members.attest', [$this->cohort, $this->member]), ['completed_on' => '2026-06-30'])
        ->assertSessionHasErrors('attestation');

    $this->actingAs($this->worker)->get(route('worker.passport.show'))
        ->assertInertia(fn (Assert $page) => $page->where('passport.evidence', fn ($items) => collect($items)->contains(
            fn ($item) => $item['issuer'] === 'Meghna TTC' && $item['state'] === 'issuer_verified' && $item['hideable'] === true,
        )));
});

it('lets only the right roles issue records, for graduates who accepted, at a verified institute', function () {
    $placement = orgMember($this->institute, OrganizationRole::PlacementOfficer);
    actingInOrg($placement, $this->institute)
        ->post(route('org.cohorts.members.attest', [$this->cohort, $this->member]), ['completed_on' => '2026-06-30'])
        ->assertForbidden();

    $invited = CohortMember::factory()->create(['cohort_id' => $this->cohort->id]);
    actingInOrg($this->trainer, $this->institute)
        ->post(route('org.cohorts.members.attest', [$this->cohort, $invited]), ['completed_on' => '2026-06-30'])
        ->assertForbidden();

    $this->institute->update(['verification_status' => Pending::class]);
    $this->post(route('org.cohorts.members.attest', [$this->cohort, $this->member]), ['completed_on' => '2026-06-30'])->assertForbidden();
    $this->get(route('org.cohorts.show', $this->cohort))->assertInertia(fn (Assert $page) => $page->where('abilities.attest', false)->where('instituteVerified', false));

    $other = Organization::factory()->institute()->verified()->create();
    actingInOrg(orgMember($other, OrganizationRole::Trainer), $other)
        ->post(route('org.cohorts.members.attest', [$this->cohort, $this->member]), ['completed_on' => '2026-06-30'])
        ->assertNotFound();
});

it('puts only a passed assessment on the Skill Passport', function () {
    actingInOrg($this->assessor, $this->institute)
        ->post(route('org.cohorts.members.assess', [$this->cohort, $this->member]), ['title' => 'Wiring test', 'result' => 'pass', 'score' => 120, 'assessed_on' => '2026-06-20'])
        ->assertSessionHasErrors('score');
    $this->post(route('org.cohorts.members.assess', [$this->cohort, $this->member]), ['title' => 'Safety test', 'result' => 'fail', 'assessed_on' => '2026-06-10'])->assertRedirect();
    $this->post(route('org.cohorts.members.assess', [$this->cohort, $this->member]), ['title' => 'Wiring test', 'result' => 'pass', 'score' => 82, 'assessed_on' => '2026-06-20'])->assertRedirect();

    expect(TradeAssessment::query()->count())->toBe(2)
        ->and(EvidenceItem::query()->where('type', EvidenceType::TradeAssessment)->count())->toBe(1);

    $this->get(route('org.cohorts.show', $this->cohort))
        ->assertInertia(fn (Assert $page) => $page->where('cohort.members.0.assessment.status.value', 'assessment_pass')->where('cohort.members.0.assessment.score', 82));
});

it('withdraws a record with a reason, which the worker sees as rejected', function () {
    $attestation = TrainingAttestation::factory()->create(['cohort_member_id' => $this->member->id, 'issued_by' => $this->trainer->id]);
    $evidence = $attestation->evidence()->create(['worker_profile_id' => $this->worker->workerProfile->id, 'type' => EvidenceType::Training, 'status' => IssuerVerified::class]);

    actingInOrg($this->trainer, $this->institute)->post(route('org.attestations.withdraw', $attestation), [])->assertSessionHasErrors('reason');
    $this->post(route('org.attestations.withdraw', $attestation), ['reason' => 'Issued to the wrong graduate.'])->assertRedirect(route('org.cohorts.show', $this->cohort));

    expect($attestation->fresh()?->status)->toBe(IssuedRecordStatus::Withdrawn)
        ->and($evidence->fresh()?->status->equals(Rejected::class))->toBeTrue()
        ->and($evidence->fresh()?->reason_code)->toBe('withdrawn_by_issuer')
        ->and($this->worker->notifications()->pluck('type')->all())->toContain('institute_record');

    $other = Organization::factory()->institute()->verified()->create();
    actingInOrg(orgMember($other, OrganizationRole::Trainer), $other)
        ->post(route('org.attestations.withdraw', $attestation), ['reason' => 'x'])->assertNotFound();
});

it('lets the worker hide an institute record from employers but not delete it', function () {
    $attestation = TrainingAttestation::factory()->create(['cohort_member_id' => $this->member->id]);
    $evidence = $attestation->evidence()->create(['worker_profile_id' => $this->worker->workerProfile->id, 'type' => EvidenceType::Training, 'status' => IssuerVerified::class]);

    expect(EvidenceSummaryData::shownToOrganizations($evidence->fresh()))->toBeTrue();

    $this->actingAs($this->worker)->delete(route('worker.passport.evidence.destroy', $evidence))->assertForbidden();
    $this->put(route('worker.passport.evidence.visibility', $evidence), [])->assertSessionHasErrors('hidden');
    $this->put(route('worker.passport.evidence.visibility', $evidence), ['hidden' => true])->assertRedirect();

    expect($evidence->fresh()?->hidden_at)->not->toBeNull()
        ->and(EvidenceSummaryData::shownToOrganizations($evidence->fresh()))->toBeFalse();

    $this->actingAs(makeWorker())->put(route('worker.passport.evidence.visibility', $evidence), ['hidden' => false])->assertNotFound();
});
