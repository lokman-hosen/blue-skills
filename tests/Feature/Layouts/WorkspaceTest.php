<?php

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Support\CurrentOrganization;
use Database\Seeders\LanguageSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
});

it('lands each user type in its own workspace', function (string $state, string $component) {
    $user = User::factory()->{$state}()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    'worker' => ['worker', 'worker/home'],
    'staff' => ['staff', 'admin/overview'],
]);

it('shows organization members their current organization and verification state', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->verified()->create(['display_name' => 'ABC Contracting']);
    $organization->users()->attach($user->id, ['joined_at' => now()]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('org/dashboard')
            ->where('currentOrganization.displayName', 'ABC Contracting')
            ->where('currentOrganization.verification.value', 'verified')
            ->where('auth.user.type', 'org')
            ->missing('auth.user.phone')
            ->has('organizations', 1));
});

it('lets a member switch to another of their organizations', function () {
    $user = User::factory()->create();
    [$first, $second] = Organization::factory()->count(2)->create();
    $first->users()->attach($user->id, ['joined_at' => now()]);
    $second->users()->attach($user->id, ['joined_at' => now()]);

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('organizations.switch', $second))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas(CurrentOrganization::SESSION_KEY, $second->id);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('currentOrganization.id', $second->id));
});

it('forbids switching to an organization the user does not belong to', function () {
    $user = User::factory()->create();
    $other = Organization::factory()->create();

    $this->actingAs($user)
        ->post(route('organizations.switch', $other))
        ->assertForbidden()
        ->assertSessionMissing(CurrentOrganization::SESSION_KEY);
});
