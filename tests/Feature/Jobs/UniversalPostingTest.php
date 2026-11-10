<?php

use App\Domain\Applications\Models\Application;
use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Jobs\Enums\ApplyMethod;
use App\Domain\Jobs\Enums\BenefitProvision;
use App\Domain\Jobs\Enums\ContractDuration;
use App\Domain\Jobs\Enums\CostPayer;
use App\Domain\Jobs\Enums\EmploymentType;
use App\Domain\Jobs\Enums\ExperienceLevel;
use App\Domain\Jobs\Enums\JobCostCategory;
use App\Domain\Jobs\Enums\SalaryBasis;
use App\Domain\Jobs\Enums\ShiftPattern;
use App\Domain\Jobs\Enums\WorkerFees;
use App\Domain\Jobs\Enums\WorkingDays;
use App\Domain\Jobs\Enums\WorkingHours;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Draft;
use App\Domain\Jobs\States\Job\Published;
use App\Domain\Jobs\States\Job\Submitted;
use App\Domain\Organization\Models\Organization;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Skill;
use Database\Seeders\CountryRuleSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TaxonomySeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

/*
 * Universal job posting (D-117): the job posting prototype's fields, options and rules.
 */

beforeEach(function () {
    $this->seed([CountrySeeder::class, CountryRuleSeeder::class, LanguageSeeder::class, TaxonomySeeder::class, RolesAndPermissionsSeeder::class]);
    $this->employer = Organization::factory()->employer()->verified()->create(['country_code' => 'AE']);
    $this->hr = orgMember($this->employer, OrganizationRole::HrAdmin);
    $this->welder = Occupation::query()->where('slug', 'welder')->firstOrFail();
    $this->job = JobPosting::factory()->create([
        'organization_id' => $this->employer->id, 'employer_organization_id' => $this->employer->id,
        'occupation_id' => $this->welder->id, 'destination_country_code' => 'PL', 'salary_currency' => 'PLN',
    ]);
});

function payStep(array $overrides = []): array
{
    return [
        'salary_amount' => 40, 'salary_max_amount' => 45, 'salary_currency' => 'PLN', 'salary_period' => 'hour',
        'salary_basis' => 'net', 'working_hours' => 'day_8_10', 'working_days' => 'days_5_6', 'shift_pattern' => 'rotating',
        'accommodation' => 'deducted', 'accommodation_cost_amount' => 400, 'food' => '', 'transport' => '', 'insurance' => '',
        ...$overrides,
    ];
}

function applyStep(array $overrides = []): array
{
    return [
        'visa_payer' => 'employer', 'permit_payer' => 'employer', 'contract_duration' => '12_36_months',
        'worker_fees' => 'none', 'costs' => [], 'apply_method' => 'blueskills', ...$overrides,
    ];
}

it('creates a job with the prototype\'s basics and shows the industry picker data', function () {
    actingInOrg($this->hr, $this->employer)->get(route('org.jobs.create'))->assertInertia(fn (Assert $page) => $page
        ->where('step', 'basics')
        ->where('steps', ['basics', 'pay', 'requirements', 'apply', 'preview'])
        ->where('organizationName', $this->employer->display_name)
        ->where('options.occupations.0.sectorSlug', 'construction-mep')
        ->where('options.currencies', fn ($currencies) => collect($currencies)->contains('EUR') && collect($currencies)->contains('MYR')));

    $this->post(route('org.jobs.store'), [
        'title' => 'MIG/MAG Welder', 'occupation_id' => $this->welder->id, 'employment_type' => 'full_time',
        'headcount' => 20, 'destination_country_code' => 'MY', 'destination_city' => 'Johor Bahru',
    ])->assertSessionHasNoErrors();

    $job = JobPosting::query()->where('title', 'MIG/MAG Welder')->sole();
    expect($job->employment_type)->toBe(EmploymentType::FullTime)
        ->and($job->apply_method)->toBe(ApplyMethod::Blueskills)
        ->and($job->public_code)->toStartWith('MY-');
});

it('stores a pay range, basis, schedule and the monthly cost of paid accommodation', function () {
    actingInOrg($this->hr, $this->employer)->put(route('org.jobs.update', [$this->job, 'pay']), payStep())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('org.jobs.edit', [$this->job, 'requirements']));

    $job = $this->job->refresh();
    expect($job->salary_amount_minor)->toBe(4000)
        ->and($job->salary_max_amount_minor)->toBe(4500)
        ->and($job->salary_basis)->toBe(SalaryBasis::Net)
        ->and($job->working_hours)->toBe(WorkingHours::Day8To10)
        ->and($job->working_days)->toBe(WorkingDays::FiveToSix)
        ->and($job->shift_pattern)->toBe(ShiftPattern::Rotating)
        ->and($job->accommodation)->toBe(BenefitProvision::Deducted)
        ->and($job->accommodation_cost_amount_minor)->toBe(40000)
        ->and($job->hours_per_day)->toBeNull()
        ->and($job->days_per_week)->toBeNull();

    // The cost belongs to paid accommodation only; choosing Free drops it.
    $this->put(route('org.jobs.update', [$this->job, 'pay']), payStep(['accommodation' => 'provided']))->assertSessionHasNoErrors();
    expect($this->job->refresh()->accommodation_cost_amount_minor)->toBeNull();
});

it('validates the pay step like the wizard does', function () {
    actingInOrg($this->hr, $this->employer);

    $this->put(route('org.jobs.update', [$this->job, 'pay']), payStep(['accommodation_cost_amount' => '']))
        ->assertSessionHasErrors(['accommodation_cost_amount' => __('jobs.validation.accommodation_cost_required')]);
    $this->put(route('org.jobs.update', [$this->job, 'pay']), payStep(['salary_max_amount' => 30]))
        ->assertSessionHasErrors(['salary_max_amount' => __('jobs.validation.salary_max_below_min')]);
    $this->put(route('org.jobs.update', [$this->job, 'pay']), payStep(['salary_amount' => '']))
        ->assertSessionHasErrors(['salary_amount' => __('jobs.validation.salary_min_required')]);
    $this->put(route('org.jobs.update', [$this->job, 'pay']), payStep(['working_hours' => 'other', 'shift_pattern' => 'other']))
        ->assertSessionHasErrors(['working_hours_other' => __('jobs.validation.other_required'), 'shift_pattern_other']);
    $this->put(route('org.jobs.update', [$this->job, 'pay']), payStep(['salary_currency' => 'XYZ', 'salary_period' => 'decade', 'salary_basis' => 'maybe']))
        ->assertSessionHasErrors(['salary_currency', 'salary_period', 'salary_basis']);

    // "Other" keeps the poster's words; any other choice clears them.
    $this->put(route('org.jobs.update', [$this->job, 'pay']), payStep(['working_hours' => 'other', 'working_hours_other' => '11 hours on weekdays']))
        ->assertSessionHasNoErrors();
    expect($this->job->refresh()->working_hours_other)->toBe('11 hours on weekdays');
    $this->put(route('org.jobs.update', [$this->job, 'pay']), payStep(['working_hours_other' => 'stale text']))->assertSessionHasNoErrors();
    expect($this->job->refresh()->working_hours_other)->toBeNull();
});

it('stores requirements and keeps matching on the experience level\'s minimum years', function () {
    $skill = Skill::query()->where('slug', 'fillet-welds')->value('id');

    actingInOrg($this->hr, $this->employer)->put(route('org.jobs.update', [$this->job, 'requirements']), [
        'experience_level' => '5_10', 'education_level' => 'diploma', 'age_range' => '18_45',
        'language_requirements' => [['code' => 'en', 'level' => 'communicative']],
        'skill_ids' => [$skill], 'other_skills' => 'Reading technical drawings',
        'duties' => 'MIG/MAG welding of steel structures. Factory welding test required.',
    ])->assertSessionHasNoErrors();

    $job = $this->job->refresh();
    expect($job->experience_level)->toBe(ExperienceLevel::FiveToTen)
        ->and($job->min_experience_years)->toBe(5)
        ->and($job->other_skills)->toBe('Reading technical drawings')
        ->and($job->duties)->toContain('Factory welding test');

    // No experience needed: matching gets no experience requirement at all.
    $this->put(route('org.jobs.update', [$this->job, 'requirements']), [
        'experience_level' => 'none', 'language_requirements' => [], 'skill_ids' => [],
    ])->assertSessionHasNoErrors();
    expect($this->job->refresh()->min_experience_years)->toBeNull();

    $this->put(route('org.jobs.update', [$this->job, 'requirements']), [
        'experience_level' => 'other', 'education_level' => 'other', 'age_range' => '17_99', 'language_requirements' => [], 'skill_ids' => [],
    ])->assertSessionHasErrors(['experience_other', 'education_other', 'age_range']);
});

it('keeps the worker fee answer consistent with the itemised costs', function () {
    actingInOrg($this->hr, $this->employer);
    $workerFee = ['category' => 'medical', 'description' => 'Medical check', 'amount' => 200, 'currency' => 'PLN', 'payer' => 'worker'];

    $this->put(route('org.jobs.update', [$this->job, 'apply']), applyStep(['worker_fees' => 'apply']))
        ->assertSessionHasErrors(['worker_fees' => __('jobs.validation.fees_need_items')]);
    $this->put(route('org.jobs.update', [$this->job, 'apply']), applyStep(['costs' => [$workerFee]]))
        ->assertSessionHasErrors(['worker_fees' => __('jobs.validation.fees_none_but_items')]);
    $this->put(route('org.jobs.update', [$this->job, 'apply']), applyStep(['visa_payer' => 'worker']))
        ->assertSessionHasErrors('worker_fees');

    $this->put(route('org.jobs.update', [$this->job, 'apply']), applyStep(['worker_fees' => 'apply', 'costs' => [$workerFee]]))
        ->assertSessionHasNoErrors();
    expect($this->job->refresh()->worker_fees)->toBe(WorkerFees::Apply)
        ->and($this->job->contract_duration)->toBe(ContractDuration::Months12To36)
        ->and($this->job->contract_months)->toBe(12);

    // Unknown is allowed either way and reads "Not disclosed".
    $this->put(route('org.jobs.update', [$this->job, 'apply']), applyStep(['worker_fees' => '', 'costs' => [$workerFee]]))->assertSessionHasNoErrors();
    expect($this->job->refresh()->worker_fees)->toBeNull();
});

it('keeps BLUESKILLS as the way to apply and shows an employer page only for "Both"', function () {
    actingInOrg($this->hr, $this->employer);

    $this->put(route('org.jobs.update', [$this->job, 'apply']), applyStep(['apply_method' => 'both']))
        ->assertSessionHasErrors(['apply_url' => __('jobs.validation.apply_url_required')]);
    $this->put(route('org.jobs.update', [$this->job, 'apply']), applyStep(['apply_method' => 'both', 'apply_url' => 'javascript:alert(1)']))
        ->assertSessionHasErrors('apply_url');
    $this->put(route('org.jobs.update', [$this->job, 'apply']), applyStep(['apply_method' => 'external']))
        ->assertSessionHasErrors('apply_method');

    $this->put(route('org.jobs.update', [$this->job, 'apply']), applyStep([
        'apply_method' => 'both', 'apply_url' => 'https://careers.example.test/welders', 'additional_details' => 'Original advertisement text',
    ]))->assertSessionHasNoErrors();
    expect($this->job->refresh()->apply_url)->toBe('https://careers.example.test/welders')
        ->and($this->job->additional_details)->toBe('Original advertisement text');

    $this->job->forceFill(['status' => Published::class, 'published_at' => now()])->save();
    $this->actingAs(makeWorker())->get(route('jobs.show', $this->job))->assertInertia(fn (Assert $page) => $page
        ->where('job.applyMethod', 'both')
        ->where('job.applyUrl', 'https://careers.example.test/welders')
        ->where('acceptsApplications', true));

    // Back to BLUESKILLS only: the link is dropped.
    actingInOrg($this->hr, $this->employer)->put(route('org.jobs.update', [$this->job, 'apply']), applyStep(['apply_url' => 'https://careers.example.test/welders']))->assertSessionHasNoErrors();
    expect($this->job->refresh()->apply_url)->toBeNull();
});

it('puts the new fields on the Truth Card and the search card', function () {
    $this->job->update([
        'salary_amount_minor' => 4000, 'salary_max_amount_minor' => 4500, 'salary_basis' => SalaryBasis::Net,
        'accommodation' => BenefitProvision::Deducted, 'accommodation_cost_amount_minor' => 40000,
        'insurance' => BenefitProvision::Provided, 'employment_type' => EmploymentType::Seasonal,
        'duties' => 'Welding', 'status' => Published::class, 'published_at' => now(),
    ]);

    $this->get(route('jobs.show', $this->job))->assertInertia(fn (Assert $page) => $page
        ->where('job.industry', 'Manufacturing')
        ->where('job.salaryMax.amountMinor', 4500)
        ->where('job.salaryBasis', 'net')
        ->where('job.accommodationCost.amountMinor', 40000)
        ->where('job.accommodationCost.currency', 'PLN')
        ->where('job.benefits.3.key', 'insurance')
        ->where('job.benefits.3.provision', 'provided')
        ->where('job.duties', 'Welding')
        ->where('job.applyUrl', null));

    $this->get(route('jobs.index'))->assertInertia(fn (Assert $page) => $page
        ->where('jobs.data.0.salaryMax.amountMinor', 4500)
        ->where('jobs.data.0.employmentType', 'seasonal'));
});

it('filters jobs by industry and employment type and searches the duties', function () {
    $this->job->update(['employment_type' => EmploymentType::Seasonal, 'duties' => 'Factory welding test required', 'status' => Published::class, 'published_at' => now()]);
    $electrician = JobPosting::factory()->published()->create([
        'employer_organization_id' => $this->employer->id, 'occupation_id' => Occupation::query()->where('slug', 'electrician')->value('id'),
        'destination_country_code' => 'AE', 'employment_type' => EmploymentType::FullTime,
    ]);
    $codes = fn (array $query) => collect($this->get(route('jobs.index', $query))->viewData('page')['props']['jobs']['data'])->pluck('code')->all();

    expect($codes(['industry' => 'manufacturing']))->toBe([$this->job->public_code])
        ->and($codes(['employment_type' => 'full_time']))->toBe([$electrician->public_code])
        ->and($codes(['employment_type' => 'nonsense']))->toHaveCount(2)
        ->and($codes(['q' => 'welding test']))->toBe([$this->job->public_code]);

    $this->get(route('jobs.index'))->assertInertia(fn (Assert $page) => $page->has('industries')->where('industries.0.slug', 'construction-mep'));
});

it('sends a live job back to review when the shift pattern changes', function () {
    actingInOrg($this->hr, $this->employer)->put(route('org.jobs.update', [$this->job, 'pay']), payStep())->assertSessionHasNoErrors();
    $this->job->forceFill(['status' => Published::class, 'published_at' => now()])->save();

    $this->put(route('org.jobs.update', [$this->job, 'requirements']), [
        'duties' => 'New duties text', 'language_requirements' => [], 'skill_ids' => [],
    ])->assertSessionHasNoErrors();
    expect($this->job->refresh()->status)->toBeInstanceOf(Published::class);

    $this->put(route('org.jobs.update', [$this->job, 'pay']), payStep(['shift_pattern' => 'night']))
        ->assertRedirect(route('org.jobs.show', $this->job));
    expect($this->job->refresh()->status)->toBeInstanceOf(Submitted::class)
        ->and($this->job->latestReview()?->notes)->toContain('shift_pattern');
});

it('redirects the step addresses used before D-117', function () {
    actingInOrg($this->hr, $this->employer);

    foreach (['requirement' => 'basics', 'terms' => 'pay', 'skills' => 'requirements', 'mobility' => 'apply'] as $old => $new) {
        $this->get(route('org.jobs.edit', [$this->job, $old]))->assertRedirect(route('org.jobs.edit', [$this->job, $new]))->assertStatus(301);
    }

    // Saving only takes the new step names; nothing is written through an old one.
    $this->put(route('org.jobs.update', [$this->job, 'terms']), payStep())->assertMethodNotAllowed();
    expect($this->job->refresh()->shift_pattern)->toBeNull();
});

it('duplicates a job into a new draft without its applications or history', function () {
    $this->job->update(['status' => Published::class, 'published_at' => now(), 'submitted_at' => now(), 'deadline' => now()->subDay(), 'language_requirements' => [['code' => 'en', 'level' => 'basic']]]);
    $this->job->skills()->attach(Skill::query()->where('slug', 'fillet-welds')->value('id'), ['id' => (string) str()->ulid(), 'required' => true]);
    $this->job->costs()->create(['category' => JobCostCategory::Medical, 'amount_minor' => 20000, 'currency' => 'PLN', 'payer' => CostPayer::Employer]);
    Application::factory()->create(['job_id' => $this->job->id, 'worker_profile_id' => makeWorker()->workerProfile->id]);

    actingInOrg($this->hr, $this->employer)->get(route('org.jobs.show', $this->job))
        ->assertInertia(fn (Assert $page) => $page->where('abilities.duplicate', true));

    $response = $this->post(route('org.jobs.duplicate', $this->job));
    $copy = JobPosting::query()->whereKeyNot($this->job->id)->sole();
    $response->assertRedirect(route('org.jobs.edit', [$copy, 'basics']));

    expect($copy->status)->toBeInstanceOf(Draft::class)
        ->and($copy->public_code)->not->toBe($this->job->public_code)
        ->and($copy->title)->toBe($this->job->title)
        ->and($copy->language_requirements)->toBe([['code' => 'en', 'level' => 'basic']])
        ->and($copy->published_at)->toBeNull()
        ->and($copy->submitted_at)->toBeNull()
        ->and($copy->deadline)->toBeNull()
        ->and($copy->skills()->count())->toBe(1)
        ->and($copy->costs()->sole()->amount_minor)->toBe(20000)
        ->and($copy->applications()->count())->toBe(0);
});

it('lets only members who post jobs duplicate their own organization\'s jobs', function () {
    $viewer = orgMember($this->employer, OrganizationRole::Viewer);
    actingInOrg($viewer, $this->employer)->post(route('org.jobs.duplicate', $this->job))->assertForbidden();

    $other = Organization::factory()->employer()->verified()->create();
    actingInOrg(orgMember($other, OrganizationRole::Owner), $other)->post(route('org.jobs.duplicate', $this->job))->assertNotFound();

    expect(JobPosting::query()->count())->toBe(1);
});

it('fills the new fields of jobs saved before D-117 without guessing', function () {
    $old = fn (array $attributes) => DB::table('jobs')->where('id', JobPosting::factory()->create([
        'organization_id' => $this->employer->id, 'employer_organization_id' => $this->employer->id,
        'occupation_id' => $this->welder->id, 'destination_country_code' => 'AE',
    ])->id)->update([
        'working_hours' => null, 'working_days' => null, 'contract_duration' => null, 'experience_level' => null,
        'worker_fees' => null, 'employment_type' => null, ...$attributes,
    ]);
    $old(['public_code' => 'OLD-1', 'hours_per_day' => 9.5, 'days_per_week' => 6, 'contract_months' => 24, 'min_experience_years' => 0]);
    $old(['public_code' => 'OLD-2', 'hours_per_day' => 13, 'days_per_week' => 7, 'contract_months' => 36, 'min_experience_years' => 4, 'visa_payer' => 'worker']);
    $old(['public_code' => 'OLD-3', 'hours_per_day' => null, 'days_per_week' => null, 'contract_months' => null, 'min_experience_years' => null]);

    $migration = require database_path('migrations/2026_11_10_100100_fill_universal_posting_fields_from_existing_jobs.php');
    $migration->up();
    $row = fn (string $code) => DB::table('jobs')->where('public_code', $code)->first();

    expect($row('OLD-1'))->toMatchArray(['working_hours' => 'day_8_10', 'working_hours_other' => null, 'working_days' => 'days_6', 'contract_duration' => '2_years', 'experience_level' => 'none', 'worker_fees' => null])
        ->and($row('OLD-2'))->toMatchArray(['working_hours' => 'other', 'working_hours_other' => '13 hours/day', 'working_days' => 'other', 'working_days_other' => '7 days/week', 'contract_duration' => 'other', 'contract_other' => '36 months', 'experience_level' => '3_5', 'worker_fees' => 'apply'])
        ->and($row('OLD-3'))->toMatchArray(['working_hours' => null, 'contract_duration' => null, 'experience_level' => null, 'worker_fees' => null]);

    // An old job still opens in every wizard step and on its Truth Card.
    $job = JobPosting::query()->where('public_code', 'OLD-3')->sole();
    actingInOrg($this->hr, $this->employer);
    foreach (['basics', 'pay', 'requirements', 'apply', 'preview'] as $step) {
        $this->get(route('org.jobs.edit', [$job, $step]))->assertOk();
    }
    $this->get(route('org.jobs.show', $job))->assertOk()->assertInertia(fn (Assert $page) => $page->where('job.workingHours', null)->where('job.applyMethod', 'blueskills'));

    $migration->down();
    expect($row('OLD-2')->working_hours)->toBeNull()
        ->and((int) $row('OLD-2')->days_per_week)->toBe(7);
});
