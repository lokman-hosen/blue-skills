<?php

use App\Domain\Identity\Channels\LogOtpChannel;
use App\Domain\Identity\Channels\WhatsAppCloudOtpChannel;
use App\Domain\Identity\Contracts\OtpChannel;
use App\Domain\Identity\Jobs\SendOtpCode;
use App\Domain\Identity\Models\OtpChallenge;
use App\Domain\Identity\Models\User;
use App\Domain\Worker\Enums\ProfileVisibility;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LanguageSeeder;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Fakes\FakeOtpChannel;

beforeEach(function () {
    $this->seed([CountrySeeder::class, LanguageSeeder::class]);
    $this->channel = new FakeOtpChannel;
    $this->app->instance(OtpChannel::class, $this->channel);
});

function requestCode(string $number = '01711000099', string $country = 'BD')
{
    return test()->post(route('worker.phone.store'), ['country' => $country, 'phone' => $number]);
}

it('shows the language and phone steps to guests', function () {
    $this->get(route('worker.join'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('auth/worker/language'));
    $this->get(route('worker.phone'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('auth/worker/phone')
        ->has('countries', 5));
});

it('registers a new worker by phone', function () {
    requestCode()->assertRedirect(route('worker.verify'));

    expect($this->channel->sent)->toHaveCount(1)
        ->and($this->channel->sent[0]['phone'])->toBe('+8801711000099')
        ->and(OtpChallenge::query()->firstOrFail()->code_hash)->not->toBe($this->channel->lastCode());

    $this->get(route('worker.verify'))->assertInertia(fn (Assert $page) => $page
        ->component('auth/worker/verify')
        ->where('maskedPhone', '+880•••••••099'));

    $this->post(route('worker.verify.store'), ['code' => $this->channel->lastCode()])
        ->assertRedirect(route('worker.details'));

    // Straight on to onboarding phase A (Module 2).
    $this->post(route('worker.details.store'), ['name' => 'Rahim Uddin', 'country' => 'BD'])
        ->assertRedirect(route('worker.basics.edit'));

    $user = User::query()->where('phone', '+8801711000099')->firstOrFail();

    $this->assertAuthenticatedAs($user);
    expect($user->isWorker())->toBeTrue()
        ->and($user->phone_verified_at)->not->toBeNull()
        ->and($user->workerProfile?->visibility)->toBe(ProfileVisibility::ApplicationsOnly)
        ->and($user->workerProfile?->current_country_code)->toBe('BD')
        ->and($user->workerProfile?->reference)->toMatch('/^BSK-\d{7}$/');
});

it('does not accept a sign-in code for another purpose', function () {
    requestCode();
    OtpChallenge::query()->update(['purpose' => 'account_deletion']);

    $this->post(route('worker.verify.store'), ['code' => $this->channel->lastCode()])
        ->assertSessionHasErrors('code');

    $this->assertGuest();
});

it('signs an existing worker straight in', function () {
    $worker = User::factory()->worker()->create(['phone' => '+8801711000099']);

    requestCode();
    $this->post(route('worker.verify.store'), ['code' => $this->channel->lastCode()])
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($worker);
});

it('counts wrong codes and expires the challenge after the limit', function () {
    requestCode();
    $wrong = $this->channel->lastCode() === '000000' ? '111111' : '000000';

    $this->post(route('worker.verify.store'), ['code' => $wrong])->assertSessionHasErrors('code');

    foreach (range(2, 5) as $attempt) {
        $this->post(route('worker.verify.store'), ['code' => $wrong]);
    }

    $this->post(route('worker.verify.store'), ['code' => $this->channel->lastCode()])
        ->assertSessionHasErrors(['code' => __('auth.otp.expired')]);
    $this->assertGuest();
});

it('rejects an expired code', function () {
    requestCode();
    $this->travel(11)->minutes();

    $this->post(route('worker.verify.store'), ['code' => $this->channel->lastCode()])
        ->assertSessionHasErrors('code');
});

it('enforces the resend cooldown and hourly limit', function () {
    requestCode();
    $this->post(route('worker.resend'))->assertSessionHasErrors('code');

    foreach (range(1, 4) as $i) {
        $this->travel(61)->seconds();
        $this->post(route('worker.resend'))->assertSessionHasNoErrors();
    }

    $this->travel(61)->seconds();
    $this->post(route('worker.resend'))->assertSessionHasErrors(['code' => __('auth.otp.too_many')]);
    expect($this->channel->sent)->toHaveCount(5);
});

it('blocks deleted accounts and non-worker numbers', function (string $state) {
    $user = User::factory()->{$state}()->create(['phone' => '+8801711000099']);

    if ($state === 'worker') {
        $user->delete();
    }

    requestCode()->assertSessionHasErrors(['phone' => __('auth.phone.blocked')]);
    expect($this->channel->sent)->toBeEmpty();
})->with(['worker', 'staff']);

it('validates the phone number for the chosen country', function () {
    requestCode('12345')->assertSessionHasErrors('phone');
    requestCode('01711000099', 'AE')->assertSessionHasErrors('country');
});

it('requires a verified phone before the details step', function () {
    $this->get(route('worker.details'))->assertRedirect(route('worker.phone'));
    $this->post(route('worker.details.store'), ['name' => 'X Y', 'country' => 'BD'])->assertRedirect(route('worker.phone'));
});

it('validates the details step', function () {
    requestCode();
    $this->post(route('worker.verify.store'), ['code' => $this->channel->lastCode()]);

    $this->post(route('worker.details.store'), ['name' => '', 'country' => 'AE'])
        ->assertSessionHasErrors(['name', 'country']);
});

it('keeps signed-in users out of the join flow', function () {
    $this->actingAs(User::factory()->worker()->create())->get(route('worker.join'))->assertRedirect();
});

it('encrypts the queued code', function () {
    expect(new SendOtpCode('+8801711000099', '123456', 'bn'))->toBeInstanceOf(ShouldBeEncrypted::class);
});

it('sends the WhatsApp authentication template', function () {
    config(['services.whatsapp.token' => 'token', 'services.whatsapp.phone_number_id' => '123']);
    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid']]])]);

    (new WhatsAppCloudOtpChannel)->send('+8801711000099', '482913', 'bn');

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/123/messages')
        && $request['to'] === '8801711000099'
        && $request['template']['language']['code'] === 'bn'
        && $request['template']['components'][0]['parameters'][0]['text'] === '482913');
});

it('refuses the log channel in production', function () {
    app()->detectEnvironment(fn () => 'production');

    (new LogOtpChannel)->send('+8801711000099', '123456', 'en');
})->throws(RuntimeException::class);

it('records every wrong attempt', function () {
    requestCode();
    $wrong = $this->channel->lastCode() === '000000' ? '111111' : '000000';

    $this->post(route('worker.verify.store'), ['code' => $wrong])
        ->assertSessionHasErrors(['code' => __('auth.otp.invalid', ['remaining' => 4])]);

    expect(OtpChallenge::query()->firstOrFail()->attempts)->toBe(1);
});
