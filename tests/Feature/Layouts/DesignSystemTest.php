<?php

use App\Domain\Identity\Models\User;
use Database\Seeders\DatabaseSeeder;
use Inertia\Testing\AssertableInertia as Assert;

it('is only for staff', function () {
    $this->get(route('design.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->create())->get(route('design.index'))->assertForbidden();
    $this->actingAs(User::factory()->worker()->create())->get(route('design.index'))->assertForbidden();
});

it('renders the component gallery with seeded Truth Cards', function () {
    $this->seed(DatabaseSeeder::class);

    $this->actingAs(User::factory()->staff()->withTwoFactor()->create())
        ->get(route('design.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/design-system')
            ->has('truthCards', 3)
            ->where('truthCards.0.publicCode', 'UAE-EL-0001'));
});

it('previews every layout', function (string $layout) {
    $this->seed(DatabaseSeeder::class);

    $this->actingAs(User::factory()->staff()->withTwoFactor()->create())
        ->get(route('design.layout', $layout))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component("{$layout}/design-preview"));
})->with(['public', 'auth', 'worker', 'org', 'admin']);

it('returns 404 for unknown layouts', function () {
    $this->actingAs(User::factory()->staff()->withTwoFactor()->create())
        ->get('/_design/layouts/unknown')
        ->assertNotFound();
});
