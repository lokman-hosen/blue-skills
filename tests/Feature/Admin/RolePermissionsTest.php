<?php

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Enums\RoleKind;
use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Support\PermissionCatalog;
use App\Domain\Organization\Models\Organization;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed([CountrySeeder::class, RolesAndPermissionsSeeder::class]);
    $this->superuser = staffUser(StaffRole::Superuser);
});

/** @return list<string> */
function rolePermissions(string $name): array
{
    $permissions = Role::findByName($name, 'web')->permissions()->pluck('name')->all();
    sort($permissions);

    return $permissions;
}

/** The permissions the editor offers the role that it holds now, plus or minus some. */
function offeredWith(string $name, array $add = [], array $remove = []): array
{
    $offered = app(PermissionCatalog::class)->forKind(RoleKind::of($name));
    $held = array_intersect($offered, rolePermissions($name));

    return array_values(array_diff(array_unique([...$held, ...$add]), $remove));
}

it('lists every permission in the editor config', function () {
    $listed = app(PermissionCatalog::class)->all();

    foreach (Permission::cases() as $permission) {
        expect($listed)->toContain($permission->value);
    }
});

it('shows every role to the superuser, with people and permission counts', function () {
    $this->actingAs($this->superuser)->get(route('admin.roles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/roles/index')
            ->has('roles', count(StaffRole::cases()) + count(OrganizationRole::cases()))
            ->where('roles.0.name', 'superuser')
            ->where('roles.0.locked', true)
            ->where('roles.0.members', 1)
            ->where('roles.0.changed', false));
});

it('offers each role only the permissions that fit it', function (string $role, array $offered, array $notOffered) {
    $this->actingAs($this->superuser)->get(route('admin.roles.edit', $role))
        ->assertOk()
        ->assertInertia(function (Assert $page) use ($offered, $notOffered) {
            $names = collect($page->toArray()['props']['groups'])
                ->flatMap(fn (array $group) => collect($group['modules'])->flatMap(fn (array $module) => collect($module['permissions'])->pluck('name')))
                ->all();

            expect($names)->toContain(...$offered);
            foreach ($notOffered as $permission) {
                expect($names)->not->toContain($permission);
            }
        });
})->with([
    'employer' => ['recruiter', ['jobs.manage', 'talent.search', 'projects.manage'], ['mandates.manage', 'courses.manage', 'crm.manage']],
    'agency' => ['case_officer', ['jobs.manage', 'mandates.manage', 'compliance.review'], ['mandates.confirm', 'projects.manage', 'courses.manage']],
    'institute' => ['trainer', ['courses.manage', 'talent_pools.manage'], ['jobs.manage', 'talent.search']],
    'staff' => ['crm_agent', ['crm.manage', 'staff.manage'], ['jobs.manage', 'team.manage']],
]);

it('keeps the screen to the superuser', function () {
    $owner = orgMember(Organization::factory()->employer()->create(), OrganizationRole::Owner);

    foreach ([staffUser(StaffRole::VerificationOfficer), staffUser(StaffRole::CrmAgent), $owner] as $user) {
        $this->actingAs($user)->get(route('admin.roles.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.roles.edit', 'recruiter'))->assertForbidden();
        $this->actingAs($user)->put(route('admin.roles.update', 'recruiter'), ['permissions' => []])->assertForbidden();
        $this->actingAs($user)->post(route('admin.roles.reset', 'recruiter'))->assertForbidden();
    }

    $this->actingAs($this->superuser)->get(route('admin.roles.edit', 'not-a-role'))->assertNotFound();
});

it('changes what a role can do from the next request', function () {
    $employer = Organization::factory()->employer()->verified()->create();
    $manager = orgMember($employer, OrganizationRole::HiringManager);
    actingInOrg($manager, $employer)->get(route('org.talent.index'))->assertForbidden();

    $this->actingAs($this->superuser)
        ->put(route('admin.roles.update', 'hiring_manager'), ['permissions' => offeredWith('hiring_manager', add: ['talent.search'])])
        ->assertRedirect(route('admin.roles.edit', 'hiring_manager'))
        ->assertSessionHasNoErrors();

    actingInOrg($manager, $employer)->get(route('org.talent.index'))->assertOk();
    actingInOrg($manager, $employer)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('organizationAbilities.searchTalent', true));

    $this->actingAs($this->superuser)
        ->put(route('admin.roles.update', 'hiring_manager'), ['permissions' => offeredWith('hiring_manager', remove: ['talent.search', 'shortlists.manage'])])
        ->assertSessionHasNoErrors();

    actingInOrg($manager, $employer)->get(route('org.talent.index'))->assertForbidden();
    actingInOrg($manager, $employer)->get(route('org.shortlists.index'))->assertForbidden();

    $this->actingAs($this->superuser)->get(route('admin.roles.index'))
        ->assertInertia(fn (Assert $page) => $page->where(
            'roles',
            fn ($roles) => collect($roles)->firstWhere('name', 'hiring_manager')['changed'] === true,
        ));
});

it('changes staff roles too', function () {
    $agent = staffUser(StaffRole::CrmAgent);
    $this->actingAs($agent)->get(route('admin.billing.index'))->assertForbidden();

    $this->actingAs($this->superuser)
        ->put(route('admin.roles.update', 'crm_agent'), ['permissions' => offeredWith('crm_agent', add: ['finance.manage'])])
        ->assertSessionHasNoErrors();

    $this->actingAs($agent->fresh())->get(route('admin.billing.index'))->assertOk();
});

it('refuses locked, unsuitable and unknown permissions', function () {
    $this->actingAs($this->superuser)
        ->put(route('admin.roles.update', 'owner'), ['permissions' => offeredWith('owner', remove: ['team.manage'])])
        ->assertSessionHasErrors('permissions');

    $this->actingAs($this->superuser)
        ->put(route('admin.roles.update', 'trust_officer'), ['permissions' => offeredWith('trust_officer', add: ['staff.manage'])])
        ->assertSessionHasErrors('permissions');

    $this->actingAs($this->superuser)
        ->put(route('admin.roles.update', 'trust_officer'), ['permissions' => offeredWith('trust_officer', remove: ['admin.access'])])
        ->assertSessionHasErrors('permissions');

    $this->actingAs($this->superuser)
        ->put(route('admin.roles.update', 'recruiter'), ['permissions' => offeredWith('recruiter', add: ['courses.manage'])])
        ->assertSessionHasErrors('permissions');

    $this->actingAs($this->superuser)
        ->put(route('admin.roles.update', 'recruiter'), ['permissions' => ['jobs.view', 'made.up']])
        ->assertSessionHasErrors('permissions.1');

    $this->actingAs($this->superuser)
        ->put(route('admin.roles.update', 'superuser'), ['permissions' => []])
        ->assertSessionHasErrors('permissions');

    expect(rolePermissions('superuser'))->toHaveCount(count(StaffRole::Superuser->permissions()));
});

it('leaves permissions the editor does not offer as they are', function () {
    // Owners hold talent_pools.manage by default; it only matters for institutes, so it isn't offered.
    $this->actingAs($this->superuser)
        ->put(route('admin.roles.update', 'owner'), ['permissions' => offeredWith('owner', remove: ['billing.manage'])])
        ->assertSessionHasNoErrors();

    expect(rolePermissions('owner'))->toContain('talent_pools.manage')->not->toContain('billing.manage');
});

it('keeps the superuser\'s changes when the seeder runs again, and resets to the defaults', function () {
    $defaults = rolePermissions('viewer');

    $this->actingAs($this->superuser)
        ->put(route('admin.roles.update', 'viewer'), ['permissions' => offeredWith('viewer', add: ['shortlists.manage'])])
        ->assertSessionHasNoErrors();

    $this->seed(RolesAndPermissionsSeeder::class);
    expect(rolePermissions('viewer'))->toContain('shortlists.manage');

    $this->actingAs($this->superuser)->post(route('admin.roles.reset', 'viewer'))
        ->assertRedirect(route('admin.roles.edit', 'viewer'))
        ->assertSessionHasNoErrors();

    expect(rolePermissions('viewer'))->toBe($defaults);
});

it('gives a permission added in code to its default roles once, without undoing changes', function () {
    Role::findByName('recruiter', 'web')->revokePermissionTo('jobs.submit');
    Spatie\Permission\Models\Permission::findByName('projects.manage', 'web')->delete();

    $this->seed(RolesAndPermissionsSeeder::class);

    expect(rolePermissions('recruiter'))->toContain('projects.manage')->not->toContain('jobs.submit')
        ->and(rolePermissions('viewer'))->not->toContain('projects.manage');
});

it('writes every change to the audit log', function () {
    $this->actingAs($this->superuser)
        ->put(route('admin.roles.update', 'viewer'), ['permissions' => offeredWith('viewer', add: ['shortlists.manage'])]);
    $this->actingAs($this->superuser)->post(route('admin.roles.reset', 'viewer'));

    $entries = Activity::query()->where('causer_id', $this->superuser->id)->orderBy('id')->get();
    expect($entries->pluck('description')->all())->toBe(['role.permissions_updated', 'role.permissions_reset'])
        ->and($entries[0]->properties['role'])->toBe('viewer')
        ->and($entries[0]->properties['attributes']['permissions'])->toContain('shortlists.manage')
        ->and($entries[0]->properties['old']['permissions'])->not->toContain('shortlists.manage');
});
