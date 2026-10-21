<?php

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\TeamContext;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Support\CurrentOrganization;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/**
 * A worker straight after phone sign-up, with an empty Skill Passport (Module 2 tests).
 *
 * @param  array<string, mixed>  $profile
 */
function makeWorker(array $profile = []): User
{
    $user = User::factory()->worker()->create();
    WorkerProfile::factory()->fresh()->for($user)->create(['current_country_code' => 'BD', ...$profile]);

    return $user->load('workerProfile');
}

/** A member of the organization with one role (needs RolesAndPermissionsSeeder). */
function orgMember(Organization $organization, OrganizationRole $role): User
{
    $user = User::factory()->create();
    $organization->users()->attach($user->id, ['joined_at' => now()]);
    TeamContext::run($organization->id, fn () => $user->assignRole($role->value));

    return $user;
}

/** Signed in as the member, with the organization as the current workspace. */
function actingInOrg(User $user, Organization $organization)
{
    return test()->actingAs($user)->withSession([CurrentOrganization::SESSION_KEY => $organization->id]);
}

/** A staff member with two-factor set up and one staff role (needs RolesAndPermissionsSeeder). */
function staffUser(StaffRole $role): User
{
    return User::factory()->staff()->withTwoFactor()->create()->assignStaffRole($role);
}
