<?php

use App\Domain\Identity\Models\User;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Verification\States\Evidence\UploadedUnreviewed;
use App\Domain\Worker\Enums\SkillSource;
use App\Domain\Worker\Models\WorkerExperience;
use App\Domain\Worker\Models\WorkerProfile;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\TaxonomySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, LanguageSeeder::class, TaxonomySeeder::class]);
    Storage::fake('private');
    $this->worker = makeWorker();
    $this->profile = $this->worker->workerProfile;
});

function fakePdf(string $name = 'certificate.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF");
}

it('shows the Passport and its edit sections to its worker only', function () {
    $this->actingAs($this->worker)->get(route('worker.passport.show'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('worker/passport/show')
            ->where('passport.reference', $this->profile->reference)
            ->where('passport.phoneVerified', true)
            ->where('passport.visibility', 'applications_only'));

    foreach (['about', 'experience', 'skills', 'languages', 'preferences', 'photo', 'evidence'] as $section) {
        $this->get(route('worker.passport.edit', $section))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('worker/passport/edit')->where('section', $section));
    }

    $this->get(route('worker.passport.edit', 'unknown'))->assertNotFound();
    $this->actingAs(User::factory()->create())->get(route('worker.passport.show'))->assertForbidden();
});

it('updates the about section', function () {
    $welder = Occupation::query()->where('slug', 'welder')->firstOrFail();

    $this->actingAs($this->worker)->put(route('worker.passport.about.update'), [
        'headline' => 'MIG/MAG welder',
        'occupation_id' => $welder->id,
        'years_experience' => 6,
        'current_country_code' => 'NP',
    ])->assertRedirect(route('worker.passport.show'));

    expect($this->profile->refresh())
        ->headline->toBe('MIG/MAG welder')
        ->occupation_id->toBe($welder->id)
        ->years_experience->toBe(6)
        ->current_country_code->toBe('NP');

    $this->put(route('worker.passport.about.update'), ['occupation_id' => 'nope', 'years_experience' => -1, 'current_country_code' => 'XX'])
        ->assertSessionHasErrors(['occupation_id', 'years_experience', 'current_country_code']);
});

it('adds, edits and deletes work history with years or dates', function () {
    $this->actingAs($this->worker)->post(route('worker.passport.experiences.store'), [
        'country_code' => 'AE', 'role' => 'Electrician', 'years' => 3,
    ])->assertRedirect(route('worker.passport.edit', 'experience'));

    $experience = WorkerExperience::query()->firstOrFail();
    expect($experience->years)->toBe(3)->and($experience->yearsWorked())->toBe(3);

    // Dates replace a stated number of years so the two never disagree.
    $this->put(route('worker.passport.experiences.update', $experience), [
        'country_code' => 'AE', 'role' => 'Electrician', 'years' => 9,
        'started_on' => '2021-02-01', 'ended_on' => '2023-12-31',
    ])->assertSessionHasNoErrors();

    expect($experience->refresh()->years)->toBeNull()
        ->and($experience->started_on->toDateString())->toBe('2021-02-01')
        ->and($experience->yearsWorked())->toBe(3);

    $this->post(route('worker.passport.experiences.store'), ['country_code' => 'AE', 'role' => 'Electrician'])
        ->assertSessionHasErrors(['years', 'started_on']);
    $this->post(route('worker.passport.experiences.store'), [
        'country_code' => 'AE', 'role' => 'Electrician', 'started_on' => '2024-01-01', 'ended_on' => '2023-01-01',
    ])->assertSessionHasErrors('ended_on');

    $this->delete(route('worker.passport.experiences.destroy', $experience))->assertRedirect();
    expect(WorkerExperience::query()->count())->toBe(0);
});

it('never lets a worker touch another worker\'s work history', function () {
    $other = WorkerExperience::factory()->for(WorkerProfile::factory()->for(User::factory()->worker()), 'workerProfile')->create(['country_code' => 'AE']);

    $this->actingAs($this->worker)->put(route('worker.passport.experiences.update', $other), [
        'country_code' => 'AE', 'role' => 'Changed', 'years' => 1,
    ])->assertNotFound();
    $this->delete(route('worker.passport.experiences.destroy', $other))->assertNotFound();

    expect($other->refresh()->role)->not->toBe('Changed');
});

it('replaces the worker\'s own skills but keeps institute attestations', function () {
    [$wiring, $termination, $maintenance] = Skill::query()->whereIn('slug', ['industrial-wiring', 'cable-termination', 'maintenance'])->orderBy('slug')->get()->all();
    $this->profile->workerSkills()->create(['skill_id' => $wiring->id, 'source' => SkillSource::Voice, 'confirmed_at' => now()]);
    $this->profile->workerSkills()->create(['skill_id' => $maintenance->id, 'source' => SkillSource::Institute, 'confirmed_at' => now()]);

    $this->actingAs($this->worker)->put(route('worker.passport.skills.update'), ['skill_ids' => [$termination->id]])
        ->assertRedirect(route('worker.passport.show'));

    expect($this->profile->workerSkills()->pluck('skill_id')->all())->toEqualCanonicalizing([$termination->id, $maintenance->id]);

    $this->put(route('worker.passport.skills.update'), ['skill_ids' => ['missing']])->assertSessionHasErrors('skill_ids.0');
});

it('saves languages and preferences with salary in minor units', function () {
    $this->actingAs($this->worker)->put(route('worker.passport.languages.update'), [
        'languages' => [['code' => 'bn', 'proficiency' => 'native'], ['code' => 'en', 'proficiency' => 'basic']],
    ])->assertRedirect(route('worker.passport.show'));

    expect($this->profile->languages()->pluck('proficiency', 'language_code')->map->value->all())
        ->toBe(['bn' => 'native', 'en' => 'basic']);

    $this->put(route('worker.passport.preferences.update'), [
        'passport_status' => 'ready', 'passport_valid_until_year' => 2030, 'availability' => 'within_30_days',
        'destinations' => ['AE', 'SA'], 'salary_amount' => 2200, 'salary_currency' => 'AED', 'salary_period' => 'month',
    ])->assertRedirect(route('worker.passport.show'));

    expect($this->profile->refresh())
        ->salary_expectation_minor->toBe(220000)
        ->salary_currency->toBe('AED')
        ->and($this->profile->destinationPreferences()->pluck('country_code')->all())->toBe(['AE', 'SA']);

    // Omani rial has three decimals; the Japanese yen has none.
    $this->put(route('worker.passport.preferences.update'), [
        'passport_status' => 'ready', 'availability' => 'immediately', 'destinations' => [],
        'salary_amount' => 300, 'salary_currency' => 'OMR', 'salary_period' => 'month',
    ]);
    expect($this->profile->refresh()->salary_expectation_minor)->toBe(300000);

    $this->put(route('worker.passport.preferences.update'), [
        'passport_status' => 'lost', 'availability' => 'tomorrow', 'destinations' => ['BD'], 'salary_amount' => 10,
    ])->assertSessionHasErrors(['passport_status', 'availability', 'destinations.0', 'salary_currency', 'salary_period']);
});

it('stores the profile photo privately and serves it only through a signed link to its worker', function () {
    $this->actingAs($this->worker)->post(route('worker.passport.photo.update'), [
        'photo' => UploadedFile::fake()->image('me.jpg', 400, 400),
    ])->assertRedirect(route('worker.passport.show'));

    $media = $this->profile->refresh()->getFirstMedia(WorkerProfile::PHOTO_COLLECTION);
    expect($media?->disk)->toBe('private');

    $url = null;
    $this->get(route('worker.passport.show'))->assertInertia(function (Assert $page) use (&$url) {
        $url = $page->toArray()['props']['passport']['photoUrl'];
    });

    expect($url)->toContain('signature=');
    $this->get($url)->assertOk();
    $this->get(route('media.show', $media->uuid))->assertForbidden();
    $this->actingAs(makeWorker())->get($url)->assertForbidden();

    $this->actingAs($this->worker)->post(route('worker.passport.photo.update'), [
        'photo' => UploadedFile::fake()->image('tiny.jpg', 50, 50),
    ])->assertSessionHasErrors('photo');

    $this->delete(route('worker.passport.photo.destroy'));
    expect($this->profile->refresh()->getFirstMedia(WorkerProfile::PHOTO_COLLECTION))->toBeNull();
});

it('uploads certificates as unreviewed evidence and lets the worker remove them', function () {
    $this->actingAs($this->worker)->post(route('worker.passport.evidence.store'), [
        'type' => 'certificate', 'title' => 'Electrical training certificate', 'document' => fakePdf(),
    ])->assertRedirect(route('worker.passport.edit', 'evidence'));

    $evidence = EvidenceItem::query()->firstOrFail();
    expect($evidence->status)->toBeInstanceOf(UploadedUnreviewed::class)
        ->and($evidence->documentable->getFirstMedia('file')?->disk)->toBe('private');

    $this->get(route('worker.passport.show'))->assertInertia(fn (Assert $page) => $page
        ->where('passport.evidence.0.state', 'uploaded_unreviewed')
        ->where('passport.evidence.0.removable', true));

    // Passport scans wait for Module 4 (D-047).
    $this->post(route('worker.passport.evidence.store'), ['type' => 'passport', 'title' => 'Passport', 'document' => fakePdf()])
        ->assertSessionHasErrors('type');
    $this->post(route('worker.passport.evidence.store'), [
        'type' => 'certificate', 'title' => 'Script', 'document' => UploadedFile::fake()->create('run.exe', 5, 'application/x-msdownload'),
    ])->assertSessionHasErrors('document');

    $this->actingAs(makeWorker())->delete(route('worker.passport.evidence.destroy', $evidence))->assertNotFound();

    $this->actingAs($this->worker)->delete(route('worker.passport.evidence.destroy', $evidence))->assertRedirect();
    expect(EvidenceItem::query()->count())->toBe(0);
});
