<?php

use App\Domain\Identity\Models\User;
use App\Domain\Organization\Models\Organization;

it('redirects guests to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('shows organization members their dashboard', function () {
    $user = User::factory()->create();
    Organization::factory()->create()->users()->attach($user->id, ['joined_at' => now()]);

    $this->actingAs($user)->get(route('dashboard'))->assertOk();
});

it('sends organization users without an organization to registration', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertRedirect(route('organizations.create'));
});
