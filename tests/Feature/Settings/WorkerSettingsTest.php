<?php

use App\Domain\Identity\Models\User;
use Database\Seeders\LanguageSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
    $this->worker = User::factory()->worker()->create(['name' => 'Rahim Uddin']);
});

it('lets workers edit their name only', function () {
    $this->actingAs($this->worker)->get(route('profile.edit'))->assertInertia(fn (Assert $page) => $page
        ->component('settings/profile')
        ->where('canEditEmail', false)
        ->where('canDeleteAccount', false));

    $this->actingAs($this->worker)
        ->patch(route('profile.update'), ['name' => 'Md Rahim Uddin', 'email' => 'someone@example.test'])
        ->assertSessionHasNoErrors();

    expect($this->worker->fresh())
        ->name->toBe('Md Rahim Uddin')
        ->email->toBeNull();
});

it('keeps workers out of password and 2FA settings', function () {
    $this->actingAs($this->worker)->get(route('security.edit'))->assertForbidden();
    $this->actingAs($this->worker)->put(route('user-password.update'), [])->assertForbidden();
    $this->actingAs($this->worker)->get(route('appearance.edit'))->assertOk();
});

it('does not let workers delete their account without phone confirmation', function () {
    $this->actingAs($this->worker)->delete(route('profile.destroy'), ['password' => 'x'])->assertForbidden();

    expect($this->worker->fresh()?->trashed())->toBeFalse();
});
