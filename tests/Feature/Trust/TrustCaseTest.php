<?php

use App\Domain\Applications\Models\Application;
use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Published;
use App\Domain\Jobs\States\Job\Suspended as JobSuspended;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\States\Verification\Suspended as OrganizationSuspended;
use App\Domain\Organization\States\Verification\Verified;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Trust\Enums\AppealStatus;
use App\Domain\Trust\Enums\CasePriority;
use App\Domain\Trust\Models\Appeal;
use App\Domain\Trust\Models\Report;
use App\Domain\Trust\Models\TrustCase;
use App\Domain\Trust\States\Report\Actioned;
use App\Domain\Trust\States\Report\Dismissed;
use App\Domain\Trust\States\TrustCase\Closed;
use App\Domain\Worker\Support\MediaUrls;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, RolesAndPermissionsSeeder::class]);
    $this->officer = staffUser(StaffRole::TrustOfficer);
    $this->employer = Organization::factory()->employer()->verified()->create(['display_name' => 'ABC Contracting']);
    $this->owner = orgMember($this->employer, OrganizationRole::Owner);
    $this->job = JobPosting::factory()->published()->create(['organization_id' => $this->employer->id, 'employer_organization_id' => $this->employer->id]);
    $this->applicant = makeWorker();
    Application::factory()->create(['job_id' => $this->job->id, 'worker_profile_id' => $this->applicant->workerProfile->id]);
    $this->reporter = makeWorker();
});

function reportJob(JobPosting $job, $reporter, string $category = 'unauthorized_payment'): Report
{
    test()->actingAs($reporter)->post(route('jobs.report', $job), ['category' => $category, 'description' => 'The agent asked me for 50,000 taka.']);

    return Report::query()->where('reporter_user_id', $reporter->id)->latest()->firstOrFail();
}

it('dismisses a report with a reason and tells the reporter only the outcome', function () {
    $report = reportJob($this->job, $this->reporter);

    $this->actingAs($this->officer)->get(route('admin.trust.reports.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/trust/reports')->where('reports.data.0.id', $report->id)->where('reports.data.0.subject', fn ($label) => str_contains($label, $this->job->public_code)));
    $this->post(route('admin.trust.reports.dismiss', $report), ['reason' => 'fake_job'])->assertSessionHasErrors('reason');
    $this->post(route('admin.trust.reports.dismiss', $report), ['reason' => 'no_evidence'])->assertRedirect();

    expect($report->fresh()?->status->equals(Dismissed::class))->toBeTrue()
        ->and($this->reporter->notifications()->latest()->first()?->data['params'])->toBe(['report_outcome' => 'no_action']);

    $this->actingAs(staffUser(StaffRole::VerificationOfficer))->get(route('admin.trust.reports.index'))->assertForbidden();
});

it('opens one case per subject, suspends the job and tells its applicants', function () {
    $first = reportJob($this->job, $this->reporter);
    $second = reportJob($this->job, makeWorker(), 'misleading_salary');

    $this->actingAs($this->officer)->post(route('admin.trust.cases.store'), ['report_id' => $first->id, 'priority' => 'high', 'summary' => 'Fee asked by recruiter.']);
    $case = TrustCase::query()->sole();
    $this->post(route('admin.trust.cases.store'), ['report_id' => $second->id, 'priority' => 'normal', 'summary' => 'x'])->assertRedirect(route('admin.trust.cases.show', $case));

    expect($case->reports()->count())->toBe(2)
        ->and($case->priority)->toBe(CasePriority::High)
        ->and($case->reference)->toStartWith('TC-');

    $this->post(route('admin.trust.cases.actions.store', [$case, 'suspend']), ['reason' => 'unauthorized_payment'])->assertSessionHasErrors('note');
    $this->post(route('admin.trust.cases.actions.store', [$case, 'suspend']), ['reason' => 'unauthorized_payment', 'note' => 'Three workers sent receipts.'])->assertRedirect();

    expect($this->job->fresh()?->status->equals(JobSuspended::class))->toBeTrue()
        ->and($this->applicant->notifications()->pluck('type')->all())->toContain('job_suspended')
        ->and($this->owner->notifications()->pluck('type')->all())->toContain('organization_suspended');
    $this->get(route('admin.trust.cases.show', $case))->assertInertia(fn (Assert $page) => $page->where('subjectSuspended', true)->has('trustCase.actions', 1));

    $this->post(route('admin.trust.cases.close', $case), ['reason' => 'unauthorized_payment']);
    expect($case->fresh()?->status->equals(Closed::class))->toBeTrue()
        ->and($first->fresh()?->status->equals(Actioned::class))->toBeTrue()
        ->and($this->reporter->notifications()->latest()->first()?->data['params'])->toBe(['report_outcome' => 'action_taken']);
});

it('makes a suspended organization read-only until another officer lifts it on appeal', function () {
    $report = reportJob($this->job, $this->reporter, 'fake_job');
    $this->actingAs($this->officer)->post(route('admin.trust.cases.store'), ['report_id' => $report->id, 'priority' => 'urgent', 'summary' => 'Fake employer.']);
    $case = TrustCase::query()->sole();
    // Widen the case to the organization.
    $case->update(['subject_type' => 'organization', 'subject_id' => $this->employer->id]);
    $this->post(route('admin.trust.cases.actions.store', [$case, 'suspend']), ['reason' => 'fake_job', 'note' => 'No trade licence on file.'])->assertRedirect();

    expect($this->employer->fresh()?->verification_status->equals(OrganizationSuspended::class))->toBeTrue()
        ->and($this->job->fresh()?->status->equals(JobSuspended::class))->toBeTrue();

    // Reading works; changing doesn't.
    actingInOrg($this->owner, $this->employer)->get(route('org.jobs.index'))->assertOk();
    $this->post(route('org.jobs.availability', [$this->job, 'close']))->assertRedirect();
    expect($this->job->fresh()?->status->equals(JobSuspended::class))->toBeTrue();

    $this->get(route('appeals.index'))->assertInertia(fn (Assert $page) => $page->component('appeals/index')->has('suspensions', 1)->where('suspensions.0.note', null)->where('suspensions.0.actor', null));
    $suspension = $case->actions()->sole();
    $this->post(route('appeals.store', $suspension), ['statement' => 'too short'])->assertSessionHasErrors('statement');
    $this->post(route('appeals.store', $suspension), ['statement' => 'We are a registered company; our licence was uploaded late.'])->assertRedirect(route('appeals.index'));
    $appeal = Appeal::query()->sole();

    $this->actingAs($this->officer)->post(route('admin.trust.appeals.decide', [$appeal, 'overturn']), ['note' => 'Licence checked.'])->assertForbidden();
    $this->actingAs(staffUser(StaffRole::TrustOfficer))->post(route('admin.trust.appeals.decide', [$appeal, 'overturn']), [])->assertSessionHasErrors('note');
    $this->post(route('admin.trust.appeals.decide', [$appeal, 'overturn']), ['note' => 'Licence checked and valid.'])->assertRedirect();

    expect($appeal->fresh()?->status)->toBe(AppealStatus::Overturned)
        ->and($this->employer->fresh()?->verification_status->equals(Verified::class))->toBeTrue()
        ->and($this->job->fresh()?->status->equals(Published::class))->toBeTrue()
        ->and($this->owner->notifications()->pluck('type')->all())->toContain('appeal_decided');
});

it('hides a suspended worker and stops them applying', function () {
    $worker = makeWorker(['occupation_id' => Occupation::factory()->create()->id]);
    $case = TrustCase::factory()->create(['subject_type' => 'worker_profile', 'subject_id' => $worker->workerProfile->id, 'category' => 'fake_credential']);

    $this->actingAs($this->officer)->post(route('admin.trust.cases.actions.store', [$case, 'suspend']), ['reason' => 'fake_credentials', 'note' => 'Certificate number belongs to someone else.']);
    expect($worker->workerProfile->fresh()?->isSuspended())->toBeTrue()
        ->and($worker->notifications()->pluck('type')->all())->toContain('account_suspended');

    $worker = $worker->fresh();
    $this->actingAs($worker)->post(route('worker.apply.store', $this->job), ['items' => []])->assertForbidden();
    $this->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page->where('suspended', true));
    $this->get(route('appeals.index'))->assertInertia(fn (Assert $page) => $page->has('suspensions', 1));

    $this->actingAs($this->officer)->post(route('admin.trust.cases.actions.store', [$case, 'restore']), ['reason' => 'not_a_breach', 'note' => 'Issuer confirmed.']);
    expect($worker->workerProfile->fresh()?->isSuspended())->toBeFalse();
});

it('keeps case evidence files to trust staff', function () {
    $case = TrustCase::factory()->create();
    $this->actingAs($this->officer)->post(route('admin.trust.cases.notes.store', $case), [
        'body' => 'Payment receipt from the worker.',
        'files' => [UploadedFile::fake()->createWithContent('receipt.pdf', "%PDF-1.4\n%%EOF")],
    ])->assertRedirect();

    $media = $case->notes()->sole()->getFirstMedia('files');
    $url = MediaUrls::signed($media);
    $this->get($url)->assertOk();
    $this->actingAs(staffUser(StaffRole::VerificationOfficer))->get($url)->assertForbidden();
    $this->actingAs(staffUser(StaffRole::VerificationOfficer))->get(route('admin.trust.cases.show', $case))->assertForbidden();
});

it('keeps a job suspended on its own when its organization is restored, and lets the employer appeal it', function () {
    $other = JobPosting::factory()->published()->create(['organization_id' => $this->employer->id, 'employer_organization_id' => $this->employer->id]);
    $jobCase = TrustCase::factory()->create(['subject_type' => 'job', 'subject_id' => $other->id]);
    $orgCase = TrustCase::factory()->create(['subject_type' => 'organization', 'subject_id' => $this->employer->id]);

    $this->actingAs($this->officer)->post(route('admin.trust.cases.actions.store', [$jobCase, 'suspend']), ['reason' => 'misleading_conditions', 'note' => 'Wrong dormitory cost.']);
    actingInOrg($this->owner, $this->employer)->get(route('appeals.index'))
        ->assertInertia(fn (Assert $page) => $page->has('suspensions', 1)->where('suspensions.0.subject', fn ($label) => str_contains($label, $other->public_code)));

    $this->actingAs($this->officer)->post(route('admin.trust.cases.actions.store', [$orgCase, 'suspend']), ['reason' => 'fake_job', 'note' => 'Checking registration.']);
    $this->post(route('admin.trust.cases.actions.store', [$orgCase, 'restore']), ['reason' => 'not_a_breach', 'note' => 'Registration fine.']);

    expect($this->job->fresh()?->status->equals(Published::class))->toBeTrue()
        ->and($other->fresh()?->status->equals(JobSuspended::class))->toBeTrue();
});
