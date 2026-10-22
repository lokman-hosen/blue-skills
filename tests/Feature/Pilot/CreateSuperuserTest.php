<?php

use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('creates the first superuser from the command line', function () {
    $this->artisan('blueskills:create-superuser', ['email' => 'ops@blueskills.test', '--name' => 'Ops Lead'])
        ->expectsQuestion('Password (12+ characters, upper and lower case, a number and a symbol)', 'Correct-Horse-42')
        ->assertSuccessful();

    $user = User::query()->where('email', 'ops@blueskills.test')->sole();
    expect($user->isStaff())->toBeTrue()
        ->and($user->hasStaffRole(StaffRole::Superuser))->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull();
});

it('refuses an email that already has an account', function () {
    User::factory()->create(['email' => 'ops@blueskills.test']);

    $this->artisan('blueskills:create-superuser', ['email' => 'ops@blueskills.test', '--name' => 'Ops Lead'])
        ->expectsQuestion('Password (12+ characters, upper and lower case, a number and a symbol)', 'Correct-Horse-42')
        ->assertFailed();

    expect(User::query()->where('email', 'ops@blueskills.test')->count())->toBe(1);
});
