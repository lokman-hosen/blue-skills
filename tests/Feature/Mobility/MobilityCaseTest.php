<?php

use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\Offer;
use App\Domain\Applications\States\Application\Offer as OfferStage;
use App\Domain\Config\Models\Country;
use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Enums\MandateStatus;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Mobility\Enums\CaseClosedReason;
use App\Domain\Mobility\Enums\MobilityStageStatus;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Mobility\States\MobilityCase\Cancelled;
use App\Domain\Mobility\States\MobilityCase\Completed;
use App\Domain\Mobility\Support\StageTemplate;
use App\Domain\Organization\Models\Organization;
use Database\Seeders\CountryRuleSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, CountryRuleSeeder::class, RolesAndPermissionsSeeder::class]);
    $this->employer = Organization::factory()->employer()->verified()->create(['display_name' => 'ABC Contracting']);
    $this->hr = orgMember($this->employer, OrganizationRole::HrAdmin);
    $this->worker = makeWorker();
});

/** An application with an open offer from `$sender`, on a job in `$country`. */
function offeredApplication(User $worker, User $sender, array $job = [], string $country = 'AE'): Application
{
    $application = Application::factory()->create([
        'job_id' => JobPosting::factory()->published()->create(['employer_organization_id' => test()->employer->id, 'destination_country_code' => $country, ...$job]),
        'worker_profile_id' => $worker->workerProfile->id,
        'status' => OfferStage::class,
    ]);
    Offer::factory()->create(['application_id' => $application->id, 'sent_by' => $sender->id]);

    return $application;
}

function acceptOffer(Application $application, User $worker): MobilityCase
{
    test()->actingAs($worker)->post(route('worker.applications.offer', [$application, 'accept']))->assertRedirect();

    return $application->mobilityCase()->firstOrFail();
}

it('opens a case from the destination template when the worker accepts the offer', function () {
    $application = offeredApplication($this->worker, $this->hr);
    $case = acceptOffer($application, $this->worker);

    expect($case->reference)->toMatch('/^MC-AE-\d{4}-\d{4}$/')
        ->and($case->managing_organization_id)->toBe($this->employer->id)
        ->and($case->agency_organization_id)->toBeNull()
        ->and($case->handler_user_id)->toBe($this->hr->id)
        ->and($case->stages()->pluck('key')->all())->toBe(array_column(StageTemplate::for('AE'), 'key'))
        ->and($case->stages()->where('key', 'job_selected')->value('status'))->toBe(MobilityStageStatus::Done)
        ->and($case->current_stage)->toBe('contract_accepted')
        ->and($this->worker->notifications()->pluck('type')->all())->toContain('case_opened');

    $this->get(route('worker.applications.show', $application))
        ->assertInertia(fn (Assert $page) => $page->where('application.mobilityCaseId', $case->id));
    $this->get(route('worker.cases.show', $case))
        ->assertInertia(fn (Assert $page) => $page->component('worker/cases/show')
            ->where('mobilityCase.current.key', 'contract_accepted')
            ->where('mobilityCase.handledBy', "{$this->hr->name} · ABC Contracting")
            ->where('mobilityCase.workerName', null)
            ->has('mobilityCase.events', 3));

    $this->actingAs(makeWorker())->get(route('worker.cases.show', $case))->assertForbidden();
});

it('uses a short generic template when the destination has none', function () {
    $case = acceptOffer(offeredApplication($this->worker, $this->hr, [], 'IN'), $this->worker);

    expect($case->stages()->pluck('key')->all())->toBe(array_column(StageTemplate::GENERIC, 'key'))
        ->and($case->current_stage)->toBe('contract_accepted');
})->skip(fn () => Country::query()->where('code', 'IN')->doesntExist(), 'needs a destination without rules');

it('lets the agency run an agency job\'s case while the employer follows it', function () {
    $agency = Organization::factory()->agency()->verified()->create(['licence_countries' => ['AE']]);
    $officer = orgMember($agency, OrganizationRole::CaseOfficer);
    $mandate = JobMandate::factory()->create(['agency_organization_id' => $agency->id, 'employer_organization_id' => $this->employer->id, 'status' => MandateStatus::Verified]);
    $case = acceptOffer(offeredApplication($this->worker, $officer, [
        'organization_id' => $agency->id, 'agency_organization_id' => $agency->id, 'job_mandate_id' => $mandate->id,
    ]), $this->worker);
    $stage = $case->stages()->where('key', 'contract_accepted')->firstOrFail();

    expect($case->managing_organization_id)->toBe($agency->id);

    actingInOrg($this->hr, $this->employer)->get(route('org.cases.show', $case))
        ->assertInertia(fn (Assert $page) => $page->component('org/cases/show')->where('canManage', false)->whereNot('mobilityCase.workerName', null));
    $this->post(route('org.cases.stages.change', [$case, $stage, 'complete']))->assertForbidden();

    $reviewer = orgMember($agency, OrganizationRole::ComplianceReviewer);
    actingInOrg($reviewer, $agency)->get(route('org.cases.show', $case))->assertInertia(fn (Assert $page) => $page->where('canManage', false));
    $this->post(route('org.cases.stages.change', [$case, $stage, 'complete']))->assertForbidden();

    actingInOrg($officer, $agency)->post(route('org.cases.stages.change', [$case, $stage, 'complete']))->assertRedirect();
    expect($case->fresh()?->current_stage)->toBe('passport_submitted');

    $other = Organization::factory()->employer()->verified()->create();
    actingInOrg(orgMember($other, OrganizationRole::Owner), $other)->get(route('org.cases.show', $case))->assertNotFound();
});

it('moves stages in order, holds them with a reason and tells the worker', function () {
    $case = acceptOffer(offeredApplication($this->worker, $this->hr), $this->worker);
    $contract = $case->stages()->where('key', 'contract_accepted')->firstOrFail();
    $medical = $case->stages()->where('key', 'medical')->firstOrFail();
    actingInOrg($this->hr, $this->employer);

    $this->post(route('org.cases.stages.change', [$case, $medical, 'complete']))
        ->assertSessionHasErrors(['stage' => __('mobility.errors.not_current')]);

    $this->post(route('org.cases.stages.change', [$case, $contract, 'complete']))->assertRedirect(route('org.cases.show', $case));
    $passport = $case->stages()->where('key', 'passport_submitted')->firstOrFail();
    expect($passport->status)->toBe(MobilityStageStatus::InProgress)
        ->and($this->worker->notifications()->pluck('type')->all())->toContain('case_updated');

    $this->post(route('org.cases.stages.change', [$case, $passport, 'block']))->assertSessionHasErrors('note');
    $this->post(route('org.cases.stages.change', [$case, $passport, 'block']), ['note' => 'Waiting for the embassy slot'])->assertRedirect();
    expect($passport->fresh()?->blocked_reason)->toBe('Waiting for the embassy slot');

    $this->post(route('org.cases.stages.change', [$case, $passport, 'complete']))
        ->assertSessionHasErrors(['stage' => __('mobility.errors.not_allowed')]);
    $this->post(route('org.cases.stages.change', [$case, $passport, 'resume']))->assertRedirect();

    $this->put(route('org.cases.stages.update', [$case, $passport]), ['next_action' => 'Bring your passport to the agency office.', 'due_on' => now()->subDay()->toDateString()])
        ->assertSessionHasErrors('due_on');
    $this->put(route('org.cases.stages.update', [$case, $passport]), ['next_action' => 'Bring your passport to the agency office.', 'due_on' => now()->addWeek()->toDateString()])
        ->assertRedirect();

    $this->actingAs($this->worker)->get(route('worker.cases.show', $case))
        ->assertInertia(fn (Assert $page) => $page
            ->where('mobilityCase.current.key', 'passport_submitted')
            ->where('mobilityCase.current.nextAction', 'Bring your passport to the agency office.')
            ->where('mobilityCase.events.0.action', 'next_action_set')
            ->where('mobilityCase.events.0.actor', 'ABC Contracting'));

    $this->post(route('org.cases.stages.change', [$case, $passport, 'complete']))->assertForbidden();
});

it('closes a case as joined only when every stage is finished, and cancels it otherwise', function () {
    $case = acceptOffer(offeredApplication($this->worker, $this->hr), $this->worker);
    actingInOrg($this->hr, $this->employer);

    $this->post(route('org.cases.close', $case), ['reason' => 'joined'])
        ->assertSessionHasErrors(['reason' => __('mobility.errors.stages_left')]);
    $this->post(route('org.cases.close', $case), ['reason' => 'other'])->assertSessionHasErrors('note');

    $this->post(route('org.cases.close', $case), ['reason' => 'visa_refused'])->assertRedirect();
    expect($case->fresh()?->status)->toBeInstanceOf(Cancelled::class)
        ->and($case->fresh()?->closed_reason)->toBe(CaseClosedReason::VisaRefused)
        ->and($this->worker->notifications()->pluck('type')->all())->toContain('case_closed');

    $this->post(route('org.cases.close', $case), ['reason' => 'other', 'note' => 'Again'])->assertForbidden();
});

it('completes the case as joined when the last stage is done', function () {
    $case = acceptOffer(offeredApplication($this->worker, $this->hr), $this->worker);
    actingInOrg($this->hr, $this->employer);

    while (($key = $case->fresh()?->current_stage) !== null) {
        $stage = $case->stages()->where('key', $key)->firstOrFail();
        $this->post(route('org.cases.stages.change', [$case, $stage, $key === 'police_clearance' ? 'skip' : 'complete']), ['note' => 'Not required'])
            ->assertSessionHasNoErrors();
    }

    expect($case->fresh()?->status)->toBeInstanceOf(Completed::class)
        ->and($case->fresh()?->closed_reason)->toBe(CaseClosedReason::Joined)
        ->and($case->stages()->where('key', 'police_clearance')->value('status'))->toBe(MobilityStageStatus::Skipped);
});
