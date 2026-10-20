<?php

use App\Domain\Jobs\Enums\BenefitProvision;
use App\Domain\Jobs\Enums\CostPayer;
use App\Domain\Jobs\Enums\JobCostCategory;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Draft;
use App\Domain\Jobs\States\Job\UnderReview;
use App\Domain\Organization\Models\Organization;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Trust\Models\Report;
use App\Domain\Worker\Enums\LanguageProficiency;
use App\Domain\Worker\Enums\PassportStatus;
use App\Domain\Worker\Enums\SkillSource;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\TaxonomySeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, LanguageSeeder::class, TaxonomySeeder::class]);
    $this->employer = Organization::factory()->employer()->verified()->create(['display_name' => 'ABC Contracting']);
    $this->electrician = Occupation::query()->where('slug', 'electrician')->firstOrFail();
    $this->welder = Occupation::query()->where('slug', 'welder')->firstOrFail();

    $this->uae = JobPosting::factory()->published()->create([
        'employer_organization_id' => $this->employer->id, 'title' => 'Building Electrician', 'occupation_id' => $this->electrician->id,
        'destination_country_code' => 'AE', 'destination_city' => 'Dubai', 'min_experience_years' => 5,
        'language_requirements' => [['code' => 'en', 'level' => 'basic']],
    ]);
    $this->uae->skills()->attach(Skill::query()->whereIn('slug', ['industrial-wiring', 'db-installation'])->pluck('id')->mapWithKeys(fn ($id) => [$id => ['id' => (string) str()->ulid(), 'required' => true]])->all());

    $this->poland = JobPosting::factory()->published()->create([
        'employer_organization_id' => $this->employer->id, 'title' => 'MIG Welder', 'occupation_id' => $this->welder->id,
        'destination_country_code' => 'PL', 'destination_city' => 'Poznań', 'accommodation' => BenefitProvision::Deducted,
        'salary_amount_minor' => null, 'salary_currency' => null,
    ]);
    $this->poland->costs()->create(['category' => JobCostCategory::Visa, 'amount_minor' => 8000, 'currency' => 'PLN', 'payer' => CostPayer::Worker]);

    $this->draft = JobPosting::factory()->create(['employer_organization_id' => $this->employer->id, 'status' => Draft::class]);
});

it('lists only published jobs to guests', function () {
    $this->get(route('jobs.index'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('public/jobs/index')
        ->where('jobs.total', 2)
        ->where('jobs.data.0.match', null));
});

it('filters by text, destination, trade and conditions', function (array $query, array $codes) {
    $codes = array_map(fn (string $key) => $this->{$key}->public_code, $codes);

    $this->get(route('jobs.index', $query))->assertInertia(fn (Assert $page) => $page
        ->where('jobs.data', fn ($jobs) => collect($jobs)->pluck('code')->sort()->values()->all() === collect($codes)->sort()->values()->all()));
})->with([
    'text' => [['q' => 'welder'], ['poland']],
    'city' => [['q' => 'dubai'], ['uae']],
    'employer' => [['q' => 'abc contracting'], ['uae', 'poland']],
    'country' => [['country' => 'PL'], ['poland']],
    'trade' => [['occupation' => 'electrician'], ['uae']],
    'no worker costs' => [['no_worker_costs' => '1'], ['uae']],
    'accommodation provided' => [['accommodation' => '1'], ['uae']],
    'salary shown' => [['salary_disclosed' => '1'], ['uae']],
]);

it('shows the Truth Card of published jobs only', function () {
    $this->get(route('jobs.show', $this->uae))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('public/jobs/show')
        ->where('job.publicCode', $this->uae->public_code)
        ->where('canReport', false));

    $this->get(route('jobs.show', $this->draft))->assertForbidden();
    $this->uae->forceFill(['status' => UnderReview::class])->save();
    $this->get(route('jobs.show', $this->uae))->assertForbidden();
});

it('explains which requirements a worker meets', function () {
    $worker = makeWorker(['occupation_id' => $this->electrician->id, 'years_experience' => 3, 'passport_status' => PassportStatus::Ready]);
    $profile = $worker->workerProfile;
    $profile->workerSkills()->create(['skill_id' => Skill::query()->where('slug', 'industrial-wiring')->value('id'), 'source' => SkillSource::Voice, 'confirmed_at' => now()]);
    $profile->languages()->create(['language_code' => 'en', 'proficiency' => LanguageProficiency::Basic]);

    $this->actingAs($worker)->get(route('jobs.show', $this->uae))->assertInertia(fn (Assert $page) => $page
        ->component('worker/jobs/show')
        // Trade, two skills, English and passport met or not; experience 3 < 5 is not met.
        ->where('match.total', 6)
        ->where('match.met', 4)
        ->where('match.requirements.1.key', 'experience')
        ->where('match.requirements.1.met', false));

    $this->get(route('jobs.index'))->assertInertia(fn (Assert $page) => $page->component('worker/jobs/index')->has('jobs.data.0.match'));
});

it('lets signed-in users report a job', function () {
    $this->post(route('jobs.report', $this->uae), ['category' => 'fake_job'])->assertRedirect(route('login'));

    $worker = makeWorker();
    $this->actingAs($worker)->post(route('jobs.report', $this->uae), ['category' => 'unauthorized_payment', 'description' => 'An agent asked me for 50,000 taka.'])
        ->assertRedirect();

    $report = Report::query()->firstOrFail();
    expect($report->reporter_user_id)->toBe($worker->id)
        ->and($report->subject_id)->toBe($this->uae->id);

    $this->post(route('jobs.report', $this->uae), ['category' => 'other'])->assertSessionHasErrors('description');
    $this->post(route('jobs.report', $this->draft), ['category' => 'fake_job'])->assertForbidden();
});
