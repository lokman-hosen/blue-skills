<?php

use App\Domain\Identity\Models\User;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Specialization;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\TaxonomySeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, LanguageSeeder::class, TaxonomySeeder::class]);
    $this->worker = makeWorker();
    $this->electrician = Occupation::query()->where('slug', 'electrician')->firstOrFail();
});

it('shows the trade step to workers only', function () {
    $this->get(route('worker.basics.edit'))->assertRedirect(route('login'));

    $this->actingAs($this->worker)->get(route('worker.basics.edit'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('onboarding/worker/basics')
            ->has('occupations', Occupation::query()->count())
            ->where('occupationId', null));

    $this->actingAs(User::factory()->create())->get(route('worker.basics.edit'))->assertForbidden();
    $this->actingAs(User::factory()->staff()->create())->get(route('worker.basics.edit'))->assertForbidden();
});

it('saves the trade and experience and continues to the voice questions', function (string $intent, string $expected) {
    $specialization = Specialization::query()->where('slug', 'industrial-electrician')->firstOrFail();

    $response = $this->actingAs($this->worker)->put(route('worker.basics.update'), [
        'occupation_id' => $this->electrician->id,
        'specialization_id' => $specialization->id,
        'years_experience' => 8,
        'intent' => $intent,
    ]);

    $response->assertRedirect($expected);

    $profile = $this->worker->workerProfile->refresh();
    expect($profile->occupation_id)->toBe($this->electrician->id)
        ->and($profile->specialization_id)->toBe($specialization->id)
        ->and($profile->years_experience)->toBe(8)
        // Phase A alone never completes the profile.
        ->and($profile->profile_completed_at)->toBeNull();
})->with([
    'voice' => ['voice', '/passport/voice'],
    'typing' => ['type', '/passport/voice?mode=type'],
    'later' => ['later', '/dashboard'],
]);

it('validates the trade step', function () {
    $welder = Occupation::query()->where('slug', 'welder')->firstOrFail();
    $electricianSpecialization = Specialization::query()->where('occupation_id', $this->electrician->id)->firstOrFail();

    $this->actingAs($this->worker)->put(route('worker.basics.update'), [
        'occupation_id' => $welder->id,
        // A specialization of another occupation.
        'specialization_id' => $electricianSpecialization->id,
        'years_experience' => 61,
        'intent' => 'voice',
    ])->assertSessionHasErrors(['specialization_id', 'years_experience']);

    $this->actingAs($this->worker)->put(route('worker.basics.update'), ['intent' => 'voice'])
        ->assertSessionHasErrors(['occupation_id', 'years_experience']);

    expect($this->worker->workerProfile->refresh()->occupation_id)->toBeNull();
});

it('shows the worker home with progress and the next step', function () {
    $this->actingAs($this->worker)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->component('worker/home')
        ->where('passport.reference', $this->worker->workerProfile->reference)
        ->where('passport.completion.percent', 20)
        ->where('passport.completion.next', 'trade'));
});
