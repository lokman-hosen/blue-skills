<?php

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Support\TeamContext;
use App\Domain\Organization\Models\Organization;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/*
| Performance guards (D-106): workspace navigation checks a dozen abilities on every page, and
| must not reload the member's permissions for each one.
*/

it('loads a member\'s organization permissions once per request', function () {
    $this->seed([CountrySeeder::class, RolesAndPermissionsSeeder::class]);
    $employer = Organization::factory()->employer()->verified()->create(['country_code' => 'AE']);
    $owner = orgMember($employer, OrganizationRole::Owner);

    app(PermissionRegistrar::class)->getPermissions();
    $permissionQueries = 0;
    DB::listen(function (QueryExecuted $query) use (&$permissionQueries): void {
        // The member's own permissions and those of their roles.
        if (str_contains($query->sql, '"model_has_permissions"') || str_contains($query->sql, '"role_has_permissions"')) {
            $permissionQueries++;
        }
    });

    actingInOrg($owner, $employer)->get(route('org.jobs.index'))->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('organizationAbilities.manageTeam', true)
            ->where('organizationAbilities.viewBilling', true));

    expect($permissionQueries)->toBeLessThanOrEqual(2);
});

it('sees a role change on the next request', function () {
    $this->seed([CountrySeeder::class, RolesAndPermissionsSeeder::class]);
    $employer = Organization::factory()->employer()->verified()->create(['country_code' => 'AE']);
    $member = orgMember($employer, OrganizationRole::Viewer);

    actingInOrg($member, $employer)->get(route('org.jobs.index'))
        ->assertInertia(fn ($page) => $page->where('organizationAbilities.manageTeam', false));

    TeamContext::run($employer->id, fn () => $member->syncRoles([OrganizationRole::Owner->value]));

    actingInOrg($member->fresh(), $employer)->get(route('org.jobs.index'))
        ->assertInertia(fn ($page) => $page->where('organizationAbilities.manageTeam', true));
});
