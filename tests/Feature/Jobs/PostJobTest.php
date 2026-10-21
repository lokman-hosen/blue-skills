<?php

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Enums\CostPayer;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Closed;
use App\Domain\Jobs\States\Job\Draft;
use App\Domain\Jobs\States\Job\Paused;
use App\Domain\Jobs\States\Job\Published;
use App\Domain\Jobs\States\Job\Submitted;
use App\Domain\Organization\Models\Organization;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Verification\Enums\VerificationType;
use Database\Seeders\CountryRuleSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TaxonomySeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, CountryRuleSeeder::class, LanguageSeeder::class, TaxonomySeeder::class, RolesAndPermissionsSeeder::class]);
    $this->employer = Organization::factory()->employer()->verified()->create(['country_code' => 'AE']);
    $this->hr = orgMember($this->employer, OrganizationRole::HrAdmin);
    $this->electrician = Occupation::query()->where('slug', 'electrician')->firstOrFail();
});

/** A complete AE job saved through the four wizard steps. */
function postCompleteJob(User $user, Organization $organization, array $mobility = []): JobPosting
{
    actingInOrg($user, $organization)->post(route('org.jobs.store'), [
        'title' => 'Building Electrician',
        'occupation_id' => Occupation::query()->where('slug', 'electrician')->value('id'),
        'headcount' => 20,
        'destination_country_code' => 'AE',
        'destination_city' => 'Dubai',
    ])->assertSessionHasNoErrors();

    $job = JobPosting::query()->latest()->firstOrFail();

    test()->put(route('org.jobs.update', [$job, 'skills']), [
        'skill_ids' => Skill::query()->whereIn('slug', ['industrial-wiring', 'db-installation'])->pluck('id')->all(),
        'min_experience_years' => 3,
        'language_requirements' => [['code' => 'en', 'level' => 'basic']],
    ])->assertSessionHasNoErrors();

    test()->put(route('org.jobs.update', [$job, 'terms']), [
        'salary_amount' => 2200, 'salary_currency' => 'AED', 'salary_period' => 'month',
        'hours_per_day' => 8, 'days_per_week' => 6, 'contract_months' => 24,
    ])->assertSessionHasNoErrors();

    test()->put(route('org.jobs.update', [$job, 'mobility']), [
        'accommodation' => 'provided', 'accommodation_details' => 'Shared, 4 per room',
        'permit_payer' => 'employer', 'visa_payer' => 'employer',
        'costs' => [['category' => 'medical', 'description' => 'Medical check', 'amount' => 300, 'currency' => 'AED', 'payer' => 'employer']],
        ...$mobility,
    ])->assertRedirect(route('org.jobs.edit', [$job, 'preview']));

    return $job->refresh();
}

it('walks through the wizard and stores the structured job', function () {
    actingInOrg($this->hr, $this->employer)->get(route('org.jobs.create'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('org/jobs/wizard')->where('step', 'requirement')->where('code', null));

    $job = postCompleteJob($this->hr, $this->employer);

    expect($job->status)->toBeInstanceOf(Draft::class)
        ->and($job->public_code)->toMatch('/^AE-EL-\d{4}$/')
        ->and($job->employer_organization_id)->toBe($this->employer->id)
        ->and($job->salary_amount_minor)->toBe(220000)
        ->and($job->skills()->count())->toBe(2)
        ->and($job->costs()->firstOrFail()->amount_minor)->toBe(30000);

    $this->get(route('org.jobs.edit', [$job, 'preview']))->assertInertia(fn (Assert $page) => $page
        ->where('problems', [])
        ->where('preview.publicCode', $job->public_code)
        ->where('form.salaryAmount', 2200));
});

it('submits a complete job for review', function () {
    $job = postCompleteJob($this->hr, $this->employer);

    $this->post(route('org.jobs.submit', $job))->assertRedirect(route('org.jobs.show', $job));

    expect($job->refresh()->status)->toBeInstanceOf(Submitted::class)
        ->and($job->submitted_at)->not->toBeNull()
        ->and($job->verificationRequests()->where('type', VerificationType::Job)->count())->toBe(1);
});

it('blocks submission when workers would pay a cost the destination does not allow', function () {
    $job = postCompleteJob($this->hr, $this->employer, [
        'costs' => [['category' => 'recruitment_fee', 'amount' => 5000, 'currency' => 'AED', 'payer' => 'worker']],
    ]);

    $this->get(route('org.jobs.edit', [$job, 'preview']))->assertInertia(fn (Assert $page) => $page->has('problems.costs'));
    $this->post(route('org.jobs.submit', $job))->assertSessionHasErrors('costs');

    expect($job->refresh()->status)->toBeInstanceOf(Draft::class);
});

it('requires the destination\'s mandatory disclosures before submission', function () {
    $job = postCompleteJob($this->hr, $this->employer);
    $job->update(['salary_amount_minor' => null, 'salary_currency' => null, 'contract_months' => null]);

    $this->post(route('org.jobs.submit', $job))->assertSessionHasErrors(['salary_amount_minor', 'contract_months']);
});

it('lets unverified employers draft but not submit', function () {
    $pending = Organization::factory()->employer()->create();
    $owner = orgMember($pending, OrganizationRole::Owner);

    $job = postCompleteJob($owner, $pending);
    $this->post(route('org.jobs.submit', $job))->assertSessionHasErrors('job');

    expect($job->refresh()->status)->toBeInstanceOf(Draft::class);
});

it('keeps posting to roles with permission and jobs to their own organization', function () {
    $viewer = orgMember($this->employer, OrganizationRole::Viewer);
    actingInOrg($viewer, $this->employer)->get(route('org.jobs.index'))->assertOk();
    actingInOrg($viewer, $this->employer)->get(route('org.jobs.create'))->assertForbidden();

    $agency = Organization::factory()->agency()->verified()->create();
    $agencyOwner = orgMember($agency, OrganizationRole::AgencyOwner);
    actingInOrg($agencyOwner, $agency)->get(route('org.jobs.create'))->assertForbidden();

    $job = postCompleteJob($this->hr, $this->employer);
    $other = Organization::factory()->employer()->verified()->create();
    $otherHr = orgMember($other, OrganizationRole::HrAdmin);
    actingInOrg($otherHr, $other)->get(route('org.jobs.show', $job))->assertNotFound();
    actingInOrg($otherHr, $other)->put(route('org.jobs.update', [$job, 'terms']), ['salary_amount' => 1, 'salary_currency' => 'AED', 'salary_period' => 'month'])->assertNotFound();

    $this->actingAs(makeWorker())->get(route('org.jobs.index'))->assertForbidden();
});

it('validates each wizard step', function () {
    actingInOrg($this->hr, $this->employer)->post(route('org.jobs.store'), [
        'title' => '', 'occupation_id' => 'nope', 'headcount' => 0, 'destination_country_code' => 'BD',
    ])->assertSessionHasErrors(['title', 'occupation_id', 'headcount', 'destination_country_code']);

    $job = postCompleteJob($this->hr, $this->employer);
    $this->put(route('org.jobs.update', [$job, 'terms']), ['salary_amount' => 2000, 'hours_per_day' => 20, 'days_per_week' => 8])
        ->assertSessionHasErrors(['salary_currency', 'salary_period', 'hours_per_day', 'days_per_week']);
    $this->put(route('org.jobs.update', [$job, 'mobility']), ['costs' => [['category' => 'bribe', 'amount' => -1, 'currency' => 'XXX', 'payer' => 'nobody']]])
        ->assertSessionHasErrors(['costs.0.category', 'costs.0.amount', 'costs.0.currency', 'costs.0.payer']);
});

it('sends a live job back to review when pay or worker costs change, but not for a new deadline', function () {
    $job = postCompleteJob($this->hr, $this->employer);
    $job->forceFill(['status' => Published::class, 'published_at' => now()])->save();

    $this->put(route('org.jobs.update', [$job, 'requirement']), [
        'title' => 'Senior Building Electrician', 'occupation_id' => $this->electrician->id, 'headcount' => 25,
        'destination_country_code' => 'AE', 'destination_city' => 'Dubai',
    ])->assertSessionHasNoErrors();
    expect($job->refresh()->status)->toBeInstanceOf(Published::class);

    $this->put(route('org.jobs.update', [$job, 'terms']), [
        'salary_amount' => 1800, 'salary_currency' => 'AED', 'salary_period' => 'month',
        'hours_per_day' => 8, 'days_per_week' => 6, 'contract_months' => 24,
    ])->assertRedirect(route('org.jobs.show', $job));

    expect($job->refresh()->status)->toBeInstanceOf(Submitted::class)
        ->and($job->material_change_at)->not->toBeNull()
        ->and($job->latestReview()?->notes)->toContain('salary_amount_minor');
});

it('refuses a material change that would charge workers an unlawful fee', function () {
    $job = postCompleteJob($this->hr, $this->employer);
    $job->forceFill(['status' => Published::class, 'published_at' => now()])->save();

    $this->put(route('org.jobs.update', [$job, 'mobility']), [
        'visa_payer' => 'worker', 'accommodation' => 'provided', 'costs' => [],
    ])->assertSessionHasErrors('costs');

    expect($job->refresh()->status)->toBeInstanceOf(Published::class)
        ->and($job->visa_payer)->toBe(CostPayer::Employer);
});

it('pauses, resumes and closes a live job', function () {
    $job = postCompleteJob($this->hr, $this->employer);
    $job->forceFill(['status' => Published::class, 'published_at' => now()])->save();

    $this->post(route('org.jobs.availability', [$job, 'pause']));
    expect($job->refresh()->status)->toBeInstanceOf(Paused::class);

    $this->post(route('org.jobs.availability', [$job, 'resume']));
    expect($job->refresh()->status)->toBeInstanceOf(Published::class);

    $this->post(route('org.jobs.availability', [$job, 'archive']))->assertSessionHasErrors('job');

    $this->post(route('org.jobs.availability', [$job, 'close']));
    expect($job->refresh()->status)->toBeInstanceOf(Closed::class);
});

it('shows job counts on the organization dashboard', function () {
    postCompleteJob($this->hr, $this->employer);

    actingInOrg($this->hr, $this->employer)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->component('org/dashboard')
        ->where('counts.drafts', 1)
        ->has('recentJobs', 1)
        ->where('canPostJobs', true));
});
