<?php

use App\Domain\Applications\Enums\ConsentPurpose;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\States\Application\Applied;
use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Closed;
use App\Domain\Organization\Models\Organization;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Verification\Enums\DocumentType;
use App\Domain\Verification\Models\Document;
use App\Domain\Worker\Models\SkillShow;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TaxonomySeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, LanguageSeeder::class, TaxonomySeeder::class, RolesAndPermissionsSeeder::class]);
    $electrician = Occupation::query()->where('slug', 'electrician')->firstOrFail();
    $this->job = JobPosting::factory()->published()->create(['occupation_id' => $electrician->id, 'destination_country_code' => 'AE', 'min_experience_years' => 3]);
    $this->job->skillRequirements()->create(['skill_id' => Skill::query()->where('slug', 'industrial-wiring')->value('id'), 'required' => true]);
    $this->worker = makeWorker(['occupation_id' => $electrician->id, 'years_experience' => 8, 'headline' => 'Electrician']);
    $this->profile = $this->worker->workerProfile;
});

it('shows what the worker can share, with passport scan and phone off', function () {
    $this->actingAs($this->worker)->get(route('worker.apply.create', $this->job))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('worker/jobs/apply')
            ->where('job.code', $this->job->public_code)
            ->where('items.0.item', 'skill_passport')
            ->where('items.0.required', true)
            ->where('items.1.available', false)
            ->where('items.4.item', 'passport_scan')
            ->where('items.4.available', false)
            ->where('items.5.item', 'phone_number')
            ->where('items.5.sharedByDefault', false)
            ->where('acceptsApplications', true));
});

it('applies with a dated snapshot, per-item consent and a timeline entry', function () {
    SkillShow::factory()->approved()->create(['worker_profile_id' => $this->profile->id]);
    $scan = Document::factory()->create(['owner_user_id' => $this->worker->id, 'type' => DocumentType::Passport]);

    $this->actingAs($this->worker)
        ->post(route('worker.apply.store', $this->job), ['items' => ['skill_show', 'passport_scan']])
        ->assertRedirect();

    $application = Application::query()->sole();
    expect($application->status)->toBeInstanceOf(Applied::class)
        ->and($application->skill_show_id)->not->toBeNull()
        ->and($application->snapshot?->data['name'])->toBe($this->worker->name)
        ->and($application->snapshot?->data['match']['total'])->toBeGreaterThan(0)
        ->and($application->snapshot?->data['experiences'])->toBe([])
        ->and($application->consents()->active()->pluck('item')->map->value->sort()->values()->all())
        ->toBe(['passport_scan', 'skill_passport', 'skill_show'])
        ->and($application->consents()->where('item', 'passport_scan')->value('document_id'))->toBe($scan->id)
        ->and($application->stageEvents()->sole()->to_status)->toBe('applied')
        ->and($this->profile->consents()->pluck('purpose')->all())
        ->toEqualCanonicalizing([ConsentPurpose::ApplicationShare, ConsentPurpose::DocumentShare]);

    $this->get(route('jobs.show', $this->job))
        ->assertInertia(fn (Assert $page) => $page->where('application.id', $application->id));
});

it('keeps the snapshot when the worker edits the passport later', function () {
    $this->actingAs($this->worker)->post(route('worker.apply.store', $this->job), ['items' => []]);
    $this->profile->update(['headline' => 'Changed headline']);

    expect(Application::query()->sole()->snapshot?->data['headline'])->toBe('Electrician');
});

it('ignores items the worker has nothing to share for', function () {
    $this->actingAs($this->worker)->post(route('worker.apply.store', $this->job), ['items' => ['skill_show', 'passport_scan', 'phone_number']]);

    expect(Application::query()->sole()->consents()->active()->pluck('item')->map->value->sort()->values()->all())
        ->toBe(['phone_number', 'skill_passport']);
});

it('blocks a second application to the same job', function () {
    $this->actingAs($this->worker)->post(route('worker.apply.store', $this->job), ['items' => []]);

    $this->post(route('worker.apply.store', $this->job), ['items' => []])
        ->assertSessionHasErrors(['job' => __('applications.apply.duplicate')]);
    $this->get(route('worker.apply.create', $this->job))
        ->assertRedirect(route('worker.applications.show', Application::query()->sole()));

    expect(Application::query()->count())->toBe(1);
});

it('refuses closed jobs and workers without a trade', function () {
    $this->job->update(['deadline' => now()->subDay()]);
    $this->actingAs($this->worker)->post(route('worker.apply.store', $this->job), ['items' => []])
        ->assertSessionHasErrors(['job' => __('applications.apply.closed')]);

    // A closed job is no longer public, so workers cannot reach it at all.
    $this->job->status->transitionTo(Closed::class);
    $this->post(route('worker.apply.store', $this->job), ['items' => []])->assertForbidden();

    $newcomer = makeWorker();
    $open = JobPosting::factory()->published()->create();
    $this->actingAs($newcomer)->post(route('worker.apply.store', $open), ['items' => []])
        ->assertSessionHasErrors(['job' => __('applications.apply.needs_trade')]);

    expect(Application::query()->count())->toBe(0);
});

it('validates the shared items', function () {
    $this->actingAs($this->worker)->post(route('worker.apply.store', $this->job), ['items' => ['salary_slip']])
        ->assertSessionHasErrors('items.0');
});

it('lets only workers apply', function () {
    $employer = Organization::factory()->employer()->verified()->create();
    $owner = orgMember($employer, OrganizationRole::Owner);

    $this->actingAs($owner)->post(route('worker.apply.store', $this->job), ['items' => []])->assertForbidden();
    $this->post(route('logout'));
    $this->post(route('worker.apply.store', $this->job), ['items' => []])->assertRedirect(route('login'));

    expect(Application::query()->count())->toBe(0);
});
