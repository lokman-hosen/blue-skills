<?php

use App\Domain\Applications\Enums\ApplicationConsentItem;
use App\Domain\Applications\Enums\InterviewOutcome;
use App\Domain\Applications\Enums\OfferStatus;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\Interview;
use App\Domain\Applications\States\Application\Interview as InterviewStage;
use App\Domain\Applications\States\Application\Offer as OfferStage;
use App\Domain\Applications\States\Application\Rejected;
use App\Domain\Applications\States\Application\Shortlisted;
use App\Domain\Applications\States\Application\Withdrawn;
use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Organization\Models\Organization;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Verification\Enums\DocumentType;
use App\Domain\Verification\Models\Document;
use App\Domain\Worker\Models\SkillShow;
use App\Domain\Worker\Support\MediaUrls;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TaxonomySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, LanguageSeeder::class, TaxonomySeeder::class, RolesAndPermissionsSeeder::class]);
    Storage::fake('private');
    $this->employer = Organization::factory()->employer()->verified()->create();
    $this->recruiter = orgMember($this->employer, OrganizationRole::Recruiter);
    $this->job = JobPosting::factory()->published()->create([
        'organization_id' => $this->employer->id,
        'employer_organization_id' => $this->employer->id,
        'occupation_id' => Occupation::query()->where('slug', 'electrician')->value('id'),
        'salary_amount_minor' => 2_000_00,
        'salary_currency' => 'AED',
    ]);
});

/** A worker who applied through the real flow, sharing the given items. */
function applyAs(JobPosting $job, array $items = [], array $profile = []): Application
{
    $worker = makeWorker(['occupation_id' => $job->occupation_id, 'years_experience' => 6, ...$profile]);
    test()->actingAs($worker)->post(route('worker.apply.store', $job), ['items' => $items])->assertSessionHasNoErrors();

    return Application::query()->latest('applied_at')->latest('id')->firstOrFail();
}

it('lists a job\'s applicants with stage counts for its organization only', function () {
    applyAs($this->job);
    applyAs($this->job)->status->transitionTo(Shortlisted::class);

    actingInOrg($this->recruiter, $this->employer)->get(route('org.jobs.applicants', $this->job))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('org/jobs/applicants')
            ->where('counts.applied', 1)
            ->where('counts.shortlisted', 1)
            ->has('applicants.data', 2)
            ->where('canManage', true)
            ->where('applicants.data.0.total', fn ($total) => $total > 0));

    $this->get(route('org.jobs.applicants', [$this->job, 'stage' => 'shortlisted']))
        ->assertInertia(fn (Assert $page) => $page->has('applicants.data', 1));

    $viewer = orgMember($this->employer, OrganizationRole::Viewer);
    actingInOrg($viewer, $this->employer)->get(route('org.jobs.applicants', $this->job))
        ->assertInertia(fn (Assert $page) => $page->where('canManage', false));

    $other = Organization::factory()->employer()->verified()->create();
    actingInOrg(orgMember($other, OrganizationRole::Owner), $other)->get(route('org.jobs.applicants', $this->job))->assertNotFound();
});

it('shows only what the worker still shares', function () {
    $worker = makeWorker(['occupation_id' => $this->job->occupation_id]);
    $this->actingAs($worker)->post(route('worker.documents.store'), ['type' => 'passport', 'document' => UploadedFile::fake()->create('p.pdf', 50, 'application/pdf')]);
    $this->post(route('worker.apply.store', $this->job), ['items' => ['passport_scan', 'phone_number']]);
    $application = Application::query()->sole();
    $scan = Document::query()->where('type', DocumentType::Passport)->sole();

    actingInOrg($this->recruiter, $this->employer)->get(route('org.applicants.show', $application))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('org/applicants/show')
            ->where('applicant.phone', (string) $worker->phone)
            ->whereNot('applicant.passportScanUrl', null)
            ->where('applicant.snapshot.name', $worker->name));

    $scanUrl = MediaUrls::signed($scan->getFirstMedia(Document::FILE_COLLECTION));
    $this->get($scanUrl)->assertOk();

    $this->actingAs($worker)->delete(route('worker.applications.items.destroy', [$application, 'phone_number']));
    $this->delete(route('worker.applications.items.destroy', [$application, 'passport_scan']));

    actingInOrg($this->recruiter, $this->employer)->get(route('org.applicants.show', $application))
        ->assertInertia(fn (Assert $page) => $page
            ->where('applicant.phone', null)
            ->where('applicant.passportScanUrl', null));
    $this->get($scanUrl)->assertForbidden();
});

it('serves a shared SkillShow to the organization until the worker stops sharing it', function () {
    $application = applyAs($this->job);
    $skillShow = SkillShow::factory()->approved()->create(['worker_profile_id' => $application->worker_profile_id]);
    $application->update(['skill_show_id' => $skillShow->id]);

    expect($this->recruiter->can('view', $skillShow))->toBeFalse();

    $application->consents()->where('item', ApplicationConsentItem::SkillShow)->update(['granted' => true, 'granted_at' => now()]);
    expect($this->recruiter->can('view', $skillShow))->toBeTrue()
        ->and(orgMember(Organization::factory()->employer()->create(), OrganizationRole::Owner)->can('view', $skillShow))->toBeFalse();

    $application->consents()->where('item', ApplicationConsentItem::SkillShow)->update(['revoked_at' => now()]);
    expect($this->recruiter->can('view', $skillShow))->toBeFalse();
});

it('moves several applicants and skips those that cannot move', function () {
    $first = applyAs($this->job);
    $second = applyAs($this->job);
    $closed = applyAs($this->job);
    $closed->status->transitionTo(Withdrawn::class);

    actingInOrg($this->recruiter, $this->employer)
        ->post(route('org.applicants.move'), ['application_ids' => [$first->id, $second->id, $closed->id], 'stage' => 'shortlisted'])
        ->assertRedirect()
        ->assertInertiaFlash('toast.message', __('applications.stage.moved_some', ['moved' => 2, 'skipped' => 1]));

    expect($first->fresh()?->status)->toBeInstanceOf(Shortlisted::class)
        ->and($second->fresh()?->status)->toBeInstanceOf(Shortlisted::class)
        ->and($first->stageEvents()->get()->last()?->actor_user_id)->toBe($this->recruiter->id);

    $this->post(route('org.applicants.move'), ['application_ids' => [$first->id], 'stage' => 'offer'])->assertSessionHasErrors('stage');
    $this->post(route('org.applicants.move'), ['application_ids' => [$closed->id], 'stage' => 'reviewed'])->assertSessionHasErrors('status');
});

it('refuses stage moves from viewers and other organizations', function () {
    $application = applyAs($this->job);

    $viewer = orgMember($this->employer, OrganizationRole::Viewer);
    actingInOrg($viewer, $this->employer)
        ->post(route('org.applicants.move'), ['application_ids' => [$application->id], 'stage' => 'shortlisted'])
        ->assertForbidden();

    $other = Organization::factory()->employer()->verified()->create();
    actingInOrg(orgMember($other, OrganizationRole::Owner), $other)
        ->post(route('org.applicants.move'), ['application_ids' => [$application->id], 'stage' => 'shortlisted'])
        ->assertNotFound();

    expect($application->fresh()?->status->getValue())->toBe('applied');
});

it('rejects with a reason the worker sees and a note the worker does not', function () {
    $application = applyAs($this->job);

    actingInOrg($this->recruiter, $this->employer)->post(route('org.applicants.reject', $application), [])->assertSessionHasErrors('reason');
    $this->post(route('org.applicants.reject', $application), ['reason' => 'positions_filled', 'note' => 'Kept for next project'])->assertRedirect();

    expect($application->fresh()?->status)->toBeInstanceOf(Rejected::class);

    $this->actingAs($application->workerProfile->user)->get(route('worker.applications.show', $application))
        ->assertInertia(fn (Assert $page) => $page
            ->where('application.events.1.reasonCode', 'positions_filled')
            ->where('application.events.1.note', null)
            ->where('application.events.1.actorName', null));
});

it('assigns a recruiter from the team only', function () {
    $application = applyAs($this->job);
    $outsider = orgMember(Organization::factory()->employer()->create(), OrganizationRole::Recruiter);

    actingInOrg($this->recruiter, $this->employer)->put(route('org.applicants.assign', $application), ['assigned_user_id' => $outsider->id])
        ->assertSessionHasErrors('assigned_user_id');
    $this->put(route('org.applicants.assign', $application), ['assigned_user_id' => $this->recruiter->id])->assertRedirect();

    expect($application->fresh()?->assigned_user_id)->toBe($this->recruiter->id);

    $this->actingAs($application->workerProfile->user)->get(route('worker.applications.show', $application))
        ->assertInertia(fn (Assert $page) => $page->where('application.handledBy', "{$this->recruiter->name} · {$this->employer->display_name}"));
});

it('books interviews in the scheduler\'s time zone and records the outcome', function () {
    $application = applyAs($this->job);
    $manager = orgMember($this->employer, OrganizationRole::HiringManager);
    $manager->update(['timezone' => 'Asia/Dubai']);
    $local = now('Asia/Dubai')->addDays(2)->setTime(10, 0);

    actingInOrg($manager, $this->employer)->post(route('org.applicants.interviews.store', $application), [
        'scheduled_at' => $local->format('Y-m-d\TH:i'), 'duration_minutes' => 30, 'mode' => 'in_person',
    ])->assertSessionHasErrors('location');

    $this->post(route('org.applicants.interviews.store', $application), [
        'scheduled_at' => now('Asia/Dubai')->subHour()->format('Y-m-d\TH:i'), 'duration_minutes' => 30, 'mode' => 'video',
    ])->assertSessionHasErrors('scheduled_at');

    $this->post(route('org.applicants.interviews.store', $application), [
        'scheduled_at' => $local->format('Y-m-d\TH:i'), 'duration_minutes' => 45, 'mode' => 'in_person',
        'location' => 'Site office, Jebel Ali', 'instructions' => 'Bring safety shoes.',
    ])->assertRedirect();

    $interview = Interview::query()->sole();
    expect($interview->scheduled_at->equalTo($local->utc()))->toBeTrue()
        ->and($application->fresh()?->status)->toBeInstanceOf(InterviewStage::class);

    $this->put(route('org.interviews.update', $interview), ['outcome' => 'passed'])->assertRedirect();
    expect($interview->fresh()?->outcome)->toBe(InterviewOutcome::Passed);

    $viewer = orgMember($this->employer, OrganizationRole::Viewer);
    actingInOrg($viewer, $this->employer)->post(route('org.applicants.interviews.store', $application), [
        'scheduled_at' => $local->format('Y-m-d\TH:i'), 'duration_minutes' => 30, 'mode' => 'video',
    ])->assertForbidden();
});

it('sends an offer that never pays less than the Truth Card', function () {
    $application = applyAs($this->job);

    actingInOrg($this->recruiter, $this->employer)
        ->post(route('org.applicants.offers.store', $application), ['salary_amount' => 1500])
        ->assertSessionHasErrors(['salary_amount' => __('applications.offer.below_truth_card')]);

    $this->post(route('org.applicants.offers.store', $application), ['salary_amount' => 2200, 'start_on' => now()->addMonth()->toDateString()])
        ->assertRedirect();

    $offer = $application->offers()->sole();
    expect($offer->salary_amount_minor)->toBe(2_200_00)
        ->and($offer->salary_currency)->toBe('AED')
        ->and($offer->status)->toBe(OfferStatus::Sent)
        ->and($application->fresh()?->status)->toBeInstanceOf(OfferStage::class);

    $this->post(route('org.applicants.offers.store', $application), ['salary_amount' => 2200])
        ->assertSessionHasErrors('salary_amount');

    $manager = orgMember($this->employer, OrganizationRole::HiringManager);
    actingInOrg($manager, $this->employer)->post(route('org.applicants.offers.store', applyAs($this->job)), ['salary_amount' => 2200])
        ->assertForbidden();
});

it('shows applicant numbers on the dashboard and the job page', function () {
    applyAs($this->job);

    actingInOrg($this->recruiter, $this->employer)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('applicantCounts.total', 1)
            ->where('applicantCounts.new', 1));

    $this->get(route('org.jobs.show', $this->job))
        ->assertInertia(fn (Assert $page) => $page->where('applicants', 1));
});
