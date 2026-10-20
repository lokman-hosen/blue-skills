<?php

use App\Domain\Applications\Enums\ConsentPurpose;
use App\Domain\Identity\Contracts\OtpChannel;
use App\Domain\Identity\Enums\OtpPurpose;
use App\Domain\Identity\Models\OtpChallenge;
use App\Domain\Identity\Models\User;
use App\Domain\Worker\Enums\ProfileVisibility;
use App\Domain\Worker\Models\SkillShow;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LanguageSeeder;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Fakes\FakeOtpChannel;

beforeEach(function () {
    $this->seed([CountrySeeder::class, LanguageSeeder::class]);
    Storage::fake('private');
    $this->channel = new FakeOtpChannel;
    $this->app->instance(OtpChannel::class, $this->channel);
    $this->worker = makeWorker();
    $this->profile = $this->worker->workerProfile;
});

it('shows privacy settings to the worker only', function () {
    $this->actingAs($this->worker)->get(route('worker.privacy.show'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('worker/privacy')
            ->where('visibility', 'applications_only')
            ->has('consents', 0)
            ->where('deletionCodeSent', false));

    $this->actingAs(User::factory()->create())->get(route('worker.privacy.show'))->assertForbidden();
});

it('records widening and narrowing visibility as a revocable consent', function () {
    $this->actingAs($this->worker)->put(route('worker.privacy.visibility.update'), ['visibility' => 'employers_and_agencies'])
        ->assertRedirect(route('worker.privacy.show'));

    $consent = $this->profile->consents()->firstOrFail();
    expect($this->profile->refresh()->visibility)->toBe(ProfileVisibility::EmployersAndAgencies)
        ->and($consent->purpose)->toBe(ConsentPurpose::Discovery)
        ->and($consent->isActive())->toBeTrue();

    $this->put(route('worker.privacy.visibility.update'), ['visibility' => 'hidden']);

    expect($consent->refresh()->isActive())->toBeFalse()
        ->and($this->profile->consents()->count())->toBe(1)
        ->and($this->profile->refresh()->visibility)->toBe(ProfileVisibility::Hidden);

    $this->put(route('worker.privacy.visibility.update'), ['visibility' => 'public'])->assertSessionHasErrors('visibility');
});

it('deletes the account only with a fresh code sent to the worker\'s phone', function () {
    SkillShow::factory()->for($this->profile)->create();
    $this->actingAs($this->worker);

    $this->delete(route('worker.privacy.account.destroy'), ['code' => '123456'])->assertSessionHasErrors('code');

    $this->post(route('worker.privacy.deletion-code.store'))->assertRedirect(route('worker.privacy.show'));
    expect(OtpChallenge::query()->firstOrFail()->purpose)->toBe(OtpPurpose::AccountDeletion)
        ->and($this->channel->sent[0]['phone'])->toBe((string) $this->worker->phone);

    $wrong = $this->channel->lastCode() === '000000' ? '111111' : '000000';
    $this->delete(route('worker.privacy.account.destroy'), ['code' => $wrong])->assertSessionHasErrors('code');
    expect($this->worker->refresh()->trashed())->toBeFalse();

    $this->delete(route('worker.privacy.account.destroy'), ['code' => $this->channel->lastCode()])->assertRedirect('/');

    $this->assertGuest();
    expect(User::withTrashed()->find($this->worker->id)->trashed())->toBeTrue()
        ->and($this->profile->refresh()->visibility)->toBe(ProfileVisibility::Hidden)
        ->and(SkillShow::query()->count())->toBe(0);
});

it('does not let a deletion code sign anyone in', function () {
    $this->actingAs($this->worker)->post(route('worker.privacy.deletion-code.store'));
    $challenge = OtpChallenge::query()->firstOrFail();

    auth()->logout();
    $this->withSession(['worker_auth.challenge_id' => $challenge->id, 'worker_auth.phone' => (string) $this->worker->phone])
        ->post(route('worker.verify.store'), ['code' => $this->channel->lastCode()])
        ->assertSessionHasErrors('code');

    $this->assertGuest();
});
