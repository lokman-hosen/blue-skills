<?php

use App\Domain\Identity\Models\User;
use App\Http\Middleware\HandleInertiaRequests;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LanguageSeeder;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, LanguageSeeder::class]);
});

it('shows the 404 page for unknown addresses, knowing who is signed in', function () {
    $this->get('/no-such-page')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page->component('public/error')->where('status', 404)->where('auth.user', null)
            ->where('translations', fn ($translations) => $translations['errors.404.title'] === 'We couldn\'t find this page'));

    $this->actingAs(makeWorker())->get('/no-such-page')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page->component('public/error')->whereNot('auth.user', null));
});

it('shows the 404 page in the visitor\'s language when a record is missing', function () {
    $this->withHeader('Accept-Language', 'bn')->get('/jobs/NO-SUCH-JOB')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page->component('public/error')->where('locale.code', 'bn'));
});

it('shows the 403 page without the exception message', function () {
    $this->actingAs(makeWorker())->get(route('org.jobs.index'))
        ->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page->component('public/error')->where('status', 403)->missing('message'));
});

it('shows the 429 page when a rate limit is hit', function () {
    config(['blueskills.security.writes_per_minute' => 1]);
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('notifications.read-all'));
    $this->actingAs($user)->post(route('notifications.read-all'))
        ->assertTooManyRequests()
        ->assertInertia(fn (Assert $page) => $page->component('public/error')->where('status', 429));
});

it('sends an expired form back with a message instead of a 419 page', function () {
    Route::middleware('web')->post('/_test/expired', fn () => abort(419));

    $this->from(route('jobs.index'))->post('/_test/expired')
        ->assertRedirect(route('jobs.index'))
        ->assertInertiaFlash('toast.message', __('errors.page_expired'));
});

it('shows a standalone page for server errors and the Inertia page for Inertia visits', function () {
    config(['app.debug' => false]);
    Route::middleware('web')->get('/_test/broken', fn () => throw new RuntimeException('secret detail'));

    $this->get('/_test/broken')
        ->assertInternalServerError()
        ->assertSee(__('errors.500.title'), false)
        ->assertDontSee('secret detail');

    $version = app(HandleInertiaRequests::class)->version(request());
    $this->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => (string) $version])->get('/_test/broken')
        ->assertInternalServerError()
        ->assertJsonPath('component', 'public/error')
        ->assertJsonPath('props.status', 500);
});

it('shows the maintenance page while the site is down', function () {
    $this->artisan('down');

    try {
        $this->get('/')->assertServiceUnavailable()->assertSee(__('errors.503.title'), false);
    } finally {
        $this->artisan('up');
    }
});

it('keeps JSON errors as JSON', function () {
    $this->getJson('/no-such-page')->assertNotFound()->assertJsonStructure(['message']);
});
