<?php

use App\Domain\Identity\Models\User;
use Database\Seeders\LanguageSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
});

it('blocks deleted accounts from signing in by email', function () {
    $user = User::factory()->create(['email' => 'gone@abc.test']);
    $user->delete();

    $this->post(route('login.store'), ['email' => 'gone@abc.test', 'password' => 'password']);

    $this->assertGuest();
});

it('pre-fills the sign-up email from an invitation link', function () {
    $this->get(route('register', ['email' => 'invitee@abc.test']))
        ->assertInertia(fn (Assert $page) => $page->component('auth/register')->where('email', 'invitee@abc.test'));
});

it('registers organization users, not workers or staff', function () {
    $this->post(route('register.store'), [
        'name' => 'Sara K.',
        'email' => 'sara@abc.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect(User::query()->where('email', 'sara@abc.test')->firstOrFail()->type->value)->toBe('org');
});
