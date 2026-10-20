<?php

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\TeamContext;
use App\Domain\Organization\Models\Organization;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('uses ULID primary keys', function () {
    $user = User::factory()->create();

    expect(Str::isUlid($user->id))->toBeTrue();
});

it('stores worker phones in E.164', function () {
    $worker = User::factory()->worker()->create(['phone' => '+880 1711-000099']);

    expect($worker->fresh()?->getRawOriginal('phone'))->toBe('+8801711000099')
        ->and($worker->email)->toBeNull();
});

it('scopes organization roles to their organization', function () {
    [$abc, $other] = Organization::factory()->count(2)->create();
    $user = User::factory()->create();
    $abc->users()->attach($user->id, ['title' => 'HR Admin', 'joined_at' => now()]);

    TeamContext::run($abc->id, fn () => $user->assignRole(OrganizationRole::HrAdmin->value));

    expect(TeamContext::run($abc->id, fn () => $user->unsetRelation('roles')->unsetRelation('permissions')->hasPermissionTo('jobs.manage')))->toBeTrue()
        ->and(TeamContext::run($other->id, fn () => $user->unsetRelation('roles')->unsetRelation('permissions')->hasPermissionTo('jobs.manage')))->toBeFalse()
        ->and($user->organizations->pluck('id')->all())->toBe([$abc->id])
        ->and(Str::isUlid($user->memberships()->firstOrFail()->id))->toBeTrue();
});

it('assigns staff roles in the platform team only to staff users', function () {
    $staff = User::factory()->staff()->create();
    $staff->assignStaffRole(StaffRole::VerificationOfficer);

    $org = User::factory()->create();

    expect($staff->hasStaffRole(StaffRole::VerificationOfficer))->toBeTrue()
        ->and($staff->hasStaffPermission('verifications.review'))->toBeTrue()
        ->and($staff->hasStaffPermission('horizon.view'))->toBeFalse()
        ->and($org->hasStaffPermission('verifications.review'))->toBeFalse();
});

it('defines a role for every organization type', function (OrganizationRole $role) {
    expect($role->permissions())->not->toBeEmpty()
        ->and(OrganizationRole::forType($role->organizationType()))->toContain($role);
})->with(OrganizationRole::cases());
