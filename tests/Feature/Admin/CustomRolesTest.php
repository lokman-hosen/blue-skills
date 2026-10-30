<?php

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Models\StaffInvitation;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\TeamContext;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationInvitation;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed([CountrySeeder::class, RolesAndPermissionsSeeder::class]);
    Mail::fake();
    $this->superuser = staffUser(StaffRole::Superuser);
});

/** Creates a role through the screen and returns it. */
function createRole(array $data): Role
{
    test()->actingAs(test()->superuser)
        ->post(route('admin.roles.store'), ['copy_permissions' => true, ...$data])
        ->assertSessionHasNoErrors();

    return Role::query()->where('label', $data['label'])->firstOrFail();
}

/** @return list<string> */
function permissionsOf(Role $role): array
{
    $names = $role->fresh()->permissions->pluck('name')->all();
    sort($names);

    return $names;
}

it('creates a role from an existing one, copying its permissions', function () {
    $this->actingAs($this->superuser)
        ->post(route('admin.roles.store'), ['kind' => 'employer', 'label' => '  Site   Supervisor ', 'based_on' => 'recruiter', 'copy_permissions' => true])
        ->assertRedirect();

    $role = Role::query()->where('label', 'Site Supervisor')->firstOrFail();
    expect($role->name)->toStartWith('c_')
        ->and($role->getAttribute('kind'))->toBe('employer')
        ->and($role->getAttribute('system'))->toBeFalse()
        ->and($role->getAttribute('based_on'))->toBe('recruiter')
        ->and($role->getAttribute('created_by'))->toBe($this->superuser->id)
        ->and(permissionsOf($role))->toBe(permissionsOf(Role::findByName('recruiter', 'web')));

    $this->actingAs($this->superuser)->get(route('admin.roles.index'))
        ->assertInertia(fn (Assert $page) => $page->where(
            'roles',
            fn ($roles) => collect($roles)->contains(fn ($row) => $row['name'] === $role->name && $row['custom'] && $row['kind'] === 'employer' && $row['basedOnLabel'] === 'Recruiter' && ! $row['changed']),
        ));
});

it('starts without permissions when asked, but keeps the locks', function () {
    $employer = createRole(['kind' => 'employer', 'label' => 'Observer', 'based_on' => 'viewer', 'copy_permissions' => false]);
    expect(permissionsOf($employer))->toBe([]);

    $staff = createRole(['kind' => 'staff', 'label' => 'Content Checker', 'based_on' => 'superuser', 'copy_permissions' => true]);
    expect(permissionsOf($staff))->toContain('admin.access', 'verifications.review')->not->toContain('staff.manage');
});

it('refuses a duplicate name, a base of another kind and anyone but the superuser', function () {
    $this->actingAs($this->superuser)
        ->post(route('admin.roles.store'), ['kind' => 'employer', 'label' => 'recruiter', 'based_on' => 'viewer'])
        ->assertSessionHasErrors('label');

    $this->actingAs($this->superuser)
        ->post(route('admin.roles.store'), ['kind' => 'employer', 'label' => 'Site lead', 'based_on' => 'case_officer'])
        ->assertSessionHasErrors('based_on');

    $this->actingAs(staffUser(StaffRole::VerificationOfficer))
        ->post(route('admin.roles.store'), ['kind' => 'employer', 'label' => 'Site lead', 'based_on' => 'viewer'])
        ->assertForbidden();

    expect(Role::query()->where('system', false)->count())->toBe(0);
});

it('lets an employer give a created role to its team, and the permissions apply', function () {
    $role = createRole(['kind' => 'employer', 'label' => 'Talent Scout', 'based_on' => 'viewer', 'copy_permissions' => false]);
    $this->actingAs($this->superuser)
        ->put(route('admin.roles.update', $role->name), ['permissions' => ['jobs.view', 'talent.search']])
        ->assertSessionHasNoErrors();

    $employer = Organization::factory()->employer()->verified()->create();
    $owner = orgMember($employer, OrganizationRole::Owner);
    $member = orgMember($employer, OrganizationRole::Viewer);

    actingInOrg($owner, $employer)->get(route('team.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('roles', fn ($roles) => collect($roles)->contains($role->name))
            ->where('translations', fn ($strings) => $strings["role.{$role->name}"] === 'Talent Scout'));

    $membership = $employer->members()->where('user_id', $member->id)->firstOrFail();
    actingInOrg($owner, $employer)->patch(route('team.members.update', $membership), ['role' => $role->name])
        ->assertSessionHasNoErrors();

    actingInOrg($member, $employer)->get(route('org.talent.index'))->assertOk();
    actingInOrg($member, $employer)->get(route('org.shortlists.index'))->assertForbidden();

    actingInOrg($owner, $employer)->post(route('team.invitations.store'), ['email' => 'scout@example.test', 'role' => $role->name])
        ->assertSessionHasNoErrors();
    expect(OrganizationInvitation::query()->where('email', 'scout@example.test')->value('role'))->toBe($role->name);
});

it('keeps created roles to their kind of organization and never makes them owners', function () {
    $agencyRole = createRole(['kind' => 'agency', 'label' => 'Visa Desk', 'based_on' => 'case_officer']);
    $employerRole = createRole(['kind' => 'employer', 'label' => 'Deputy', 'based_on' => 'hr_admin']);

    $employer = Organization::factory()->employer()->create();
    $owner = orgMember($employer, OrganizationRole::Owner);

    actingInOrg($owner, $employer)->post(route('team.invitations.store'), ['email' => 'x@example.test', 'role' => $agencyRole->name])
        ->assertSessionHasErrors('role');

    $ownMembership = $employer->members()->where('user_id', $owner->id)->firstOrFail();
    actingInOrg($owner, $employer)->patch(route('team.members.update', $ownMembership), ['role' => $employerRole->name])
        ->assertSessionHasErrors('role');
});

it('lets the superuser give staff a created staff role', function () {
    $role = createRole(['kind' => 'staff', 'label' => 'Billing Assistant', 'based_on' => 'finance_officer']);
    $agent = staffUser(StaffRole::CrmAgent);

    $this->actingAs($this->superuser)->patch(route('admin.staff.members.update', $agent), ['role' => $role->name])
        ->assertSessionHasNoErrors();

    $this->actingAs($agent->fresh())->get(route('admin.billing.index'))->assertOk();
    $this->actingAs($agent->fresh())->get(route('admin.crm.index'))->assertForbidden();

    $this->actingAs($this->superuser)->post(route('admin.staff.invitations.store'), ['email' => 'billing@blueskills.test', 'role' => $role->name])
        ->assertSessionHasNoErrors();
    expect(StaffInvitation::query()->where('email', 'billing@blueskills.test')->value('role'))->toBe($role->name);
});

it('renames created roles only', function () {
    $role = createRole(['kind' => 'institute', 'label' => 'Lab Lead', 'based_on' => 'trainer']);

    $this->actingAs($this->superuser)
        ->patch(route('admin.roles.details', $role->name), ['label' => 'Workshop Lead', 'description' => 'Runs the welding workshop'])
        ->assertSessionHasNoErrors();
    expect($role->fresh()->getAttribute('label'))->toBe('Workshop Lead')
        ->and($role->fresh()->getAttribute('description'))->toBe('Runs the welding workshop');

    $this->actingAs($this->superuser)
        ->patch(route('admin.roles.details', $role->name), ['label' => 'Trainer'])
        ->assertSessionHasErrors('label');

    $this->actingAs($this->superuser)
        ->patch(route('admin.roles.details', 'trainer'), ['label' => 'Teacher'])
        ->assertSessionHasErrors('label');

    $this->actingAs($this->superuser)->post(route('admin.roles.reset', $role->name))
        ->assertSessionHasErrors('permissions');
});

it('deletes a created role only when nobody holds it and no invitation uses it', function () {
    $role = createRole(['kind' => 'employer', 'label' => 'Temp', 'based_on' => 'viewer']);
    $employer = Organization::factory()->employer()->create();
    $member = User::factory()->create();
    $employer->users()->attach($member->id, ['joined_at' => now()]);
    TeamContext::run($employer->id, fn () => $member->assignRole($role->name));

    $this->actingAs($this->superuser)->delete(route('admin.roles.destroy', $role->name))
        ->assertSessionHasErrors('role');

    TeamContext::run($employer->id, fn () => $member->unsetRelation('roles')->removeRole($role->name));
    $owner = orgMember($employer, OrganizationRole::Owner);
    actingInOrg($owner, $employer)->post(route('team.invitations.store'), ['email' => 'temp@example.test', 'role' => $role->name]);

    $this->actingAs($this->superuser)->delete(route('admin.roles.destroy', $role->name))
        ->assertSessionHasErrors('role');

    OrganizationInvitation::query()->update(['revoked_at' => now()]);
    $this->actingAs($this->superuser)->delete(route('admin.roles.destroy', $role->name))
        ->assertRedirect(route('admin.roles.index'));
    expect(Role::query()->where('name', $role->name)->exists())->toBeFalse();

    $this->actingAs($this->superuser)->delete(route('admin.roles.destroy', 'viewer'))
        ->assertSessionHasErrors('role');
    $this->actingAs(staffUser(StaffRole::TrustOfficer))->delete(route('admin.roles.destroy', 'viewer'))
        ->assertForbidden();
});

it('shows people in a created role the manual of the role it started from', function () {
    $role = createRole(['kind' => 'employer', 'label' => 'Site Supervisor', 'based_on' => 'recruiter']);
    $employer = Organization::factory()->employer()->create();
    $member = User::factory()->create();
    $employer->users()->attach($member->id, ['joined_at' => now()]);
    TeamContext::run($employer->id, fn () => $member->assignRole($role->name));

    actingInOrg($member, $employer)->get(route('manual.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('manual.role', 'recruiter')
            ->where('adaptedRole', 'Site Supervisor')
            ->has('roles', 1));

    actingInOrg($member, $employer)->get(route('manual.show', 'owner'))->assertForbidden();
});

it('writes created, renamed and deleted roles to the audit log', function () {
    $role = createRole(['kind' => 'employer', 'label' => 'Temp', 'based_on' => 'viewer']);
    $this->actingAs($this->superuser)->patch(route('admin.roles.details', $role->name), ['label' => 'Temporary']);
    $this->actingAs($this->superuser)->delete(route('admin.roles.destroy', $role->name));

    expect(Activity::query()->where('causer_id', $this->superuser->id)->orderBy('id')->pluck('description')->all())
        ->toBe(['role.created', 'role.renamed', 'role.deleted']);
});
