<?php

use App\Domain\Config\Support\LocaleRegistry;
use App\Domain\Identity\Models\User;
use App\Http\Middleware\SetLocale;
use Database\Seeders\LanguageSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
});

it('defaults to English, left to right', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('dir="ltr"', false)
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/home')
            ->where('locale.code', 'en')
            ->where('locale.dir', 'ltr')
            ->where('translations', fn ($strings) => $strings['nav.home'] === 'Home'));
});

it('switches to Urdu with ?lang and renders right to left', function () {
    $this->get('/?lang=ur')
        ->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSessionHas(SetLocale::SESSION_KEY, 'ur')
        ->assertInertia(fn (Assert $page) => $page->where('locale.code', 'ur')->where('locale.dir', 'rtl'));
});

it('falls back to English strings for keys missing in a locale', function () {
    // Urdu has no strings yet: English is shown, isolated so it reads correctly inside RTL text.
    $this->get('/?lang=ur')->assertInertia(fn (Assert $page) => $page
        ->where('translations', fn ($strings) => $strings['nav.home'] === "\u{2068}Home\u{2069}"));

    $this->get('/?lang=bn')->assertInertia(fn (Assert $page) => $page
        ->where('translations', fn ($strings) => $strings['nav.home'] === 'হোম'));
});

it('flags locales still in translation review', function () {
    $this->get('/?lang=si')->assertInertia(fn (Assert $page) => $page
        ->where('locale.code', 'si')
        ->where('locale.translationPending', true));
});

it('uses the browser language when nothing else is set', function () {
    $this->withHeader('Accept-Language', 'bn-BD,bn;q=0.9,en;q=0.8')
        ->get('/')
        ->assertInertia(fn (Assert $page) => $page->where('locale.code', 'bn'));
});

it('ignores planned and unknown languages', function (string $code) {
    $this->get("/?lang={$code}")->assertInertia(fn (Assert $page) => $page->where('locale.code', 'en'));
})->with(['ar', 'xx']);

it('remembers the chosen locale on the session and the signed-in user', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $this->actingAs($user)
        ->from('/')
        ->post(route('locale.update'), ['locale' => 'bn'])
        ->assertRedirect('/')
        ->assertSessionHas(SetLocale::SESSION_KEY, 'bn');

    expect($user->fresh()?->locale)->toBe('bn');
});

it('rejects a locale that is not available', function () {
    $this->post(route('locale.update'), ['locale' => 'ar'])->assertSessionHasErrors('locale');
});

it('keeps en.json and bn.json in sync', function () {
    $en = json_decode((string) file_get_contents(lang_path('en.json')), true);
    $bn = json_decode((string) file_get_contents(lang_path('bn.json')), true);

    expect(array_keys($bn))->toEqualCanonicalizing(array_keys($en));
});

it('reads locales back from a serializing cache store', function () {
    config(['cache.default' => 'file']);
    $registry = app(LocaleRegistry::class);
    $registry->forget();

    $registry->available();

    expect($registry->available()->pluck('code')->all())->toContain('en', 'bn', 'ur')
        ->and($registry->isRtl('ur'))->toBeTrue();

    $registry->forget();
});

it('falls back to English on the server for a locale without its own strings', function () {
    app()->setLocale('ur');

    expect(__('errors.code', ['code' => 404]))->toBe('Error 404')
        ->and(__('no.such.key'))->toBe('no.such.key');
});
