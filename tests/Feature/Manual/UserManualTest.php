<?php

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\TeamContext;
use App\Domain\Manual\Enums\ManualRole;
use App\Domain\Manual\Support\ManualLibrary;
use App\Domain\Organization\Models\Organization;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, RolesAndPermissionsSeeder::class]);
});

it('has a manual with sections for every role', function (ManualRole $role) {
    $library = app(ManualLibrary::class);

    expect($library->exists($role))->toBeTrue();

    $manual = $library->load($role, 'en');
    expect($manual->sections)->not->toBeEmpty()
        ->and($manual->sections[0]->title)->toBe('Getting started')
        ->and($manual->translationPending)->toBeFalse();
})->with(ManualRole::cases());

it('needs sign-in', function () {
    $this->get(route('manual.index'))->assertRedirect(route('login'));
    $this->get(route('manual.show', 'worker'))->assertRedirect(route('login'));
});

it('shows a worker only the worker manual', function () {
    $worker = makeWorker();

    $this->actingAs($worker)->get(route('manual.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('manual/show')
            ->where('manual.role', 'worker')
            ->where('canReadAll', false)
            ->has('roles', 1)
            ->where('roles.0.value', 'worker'));

    $this->actingAs($worker)->get(route('manual.show', 'worker'))->assertOk();
    $this->actingAs($worker)->get(route('manual.show', 'recruiter'))->assertForbidden();
    $this->actingAs($worker)->get(route('manual.show', 'superuser'))->assertForbidden();
});

it('shows an organization member the manual of their role and refuses the others', function (OrganizationRole $role, Closure $organization) {
    $member = orgMember($organization(), $role);

    actingInOrg($member, $member->organizations->first())->get(route('manual.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('manual.role', $role->value)
            ->has('roles', 1)
            ->where('canReadAll', false));

    $this->actingAs($member)->get(route('manual.show', $role->value))->assertOk();
    $this->actingAs($member)->get(route('manual.show', 'owner'))->assertForbidden();
    $this->actingAs($member)->get(route('manual.show', 'worker'))->assertForbidden();
    $this->actingAs($member)->get(route('manual.show', 'superuser'))->assertForbidden();
})->with([
    'recruiter' => [OrganizationRole::Recruiter, fn () => Organization::factory()->employer()->create()],
    'case officer' => [OrganizationRole::CaseOfficer, fn () => Organization::factory()->agency()->create()],
    'trainer' => [OrganizationRole::Trainer, fn () => Organization::factory()->institute()->create()],
]);

it('shows a member of two organizations the manuals of both roles only', function () {
    $employer = Organization::factory()->employer()->create();
    $agency = Organization::factory()->agency()->create();
    $member = orgMember($employer, OrganizationRole::Viewer);
    $agency->users()->attach($member->id, ['joined_at' => now()]);
    TeamContext::run($agency->id, fn () => $member->assignRole(OrganizationRole::ComplianceReviewer->value));

    $this->actingAs($member)->get(route('manual.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('manual.role', 'viewer')
            ->has('roles', 2)
            ->where('roles.0.value', 'viewer')
            ->where('roles.1.value', 'compliance_reviewer'));

    $this->actingAs($member)->get(route('manual.show', 'compliance_reviewer'))->assertOk();
    $this->actingAs($member)->get(route('manual.show', 'hiring_manager'))->assertForbidden();
});

it('shows staff other than the superuser only their own manual', function () {
    $agent = staffUser(StaffRole::CrmAgent);

    $this->actingAs($agent)->get(route('manual.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('manual.role', 'crm_agent')
            ->has('roles', 1)
            ->where('canReadAll', false));

    $this->actingAs($agent)->get(route('manual.show', 'finance_officer'))->assertForbidden();
    $this->actingAs($agent)->get(route('manual.show', 'superuser'))->assertForbidden();
});

it('lets the superuser open every manual, starting with their own', function () {
    $superuser = staffUser(StaffRole::Superuser);

    $this->actingAs($superuser)->get(route('manual.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('manual.role', 'superuser')
            ->where('canReadAll', true)
            ->has('roles', count(ManualRole::cases()))
            ->where('roles.0.value', 'worker')
            ->where('roles.'.(count(ManualRole::cases()) - 1).'.value', 'superuser'));

    foreach (ManualRole::cases() as $role) {
        $this->actingAs($superuser)->get(route('manual.show', $role->value))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('manual.role', $role->value));
    }
});

it('returns 404 for a role that has no manual', function () {
    $this->actingAs(staffUser(StaffRole::Superuser))->get('/manual/not-a-role')->assertNotFound();
});

it('shows an empty state to an organization user who has no role yet', function () {
    $this->actingAs(User::factory()->create())->get(route('manual.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('manual', null)->has('roles', 0));
});

it('shows the English manual with a notice when the language has none yet', function () {
    app()->setLocale('bn');

    $manual = app(ManualLibrary::class)->load(ManualRole::Worker, 'bn');

    expect($manual->translationPending)->toBeTrue()
        ->and($manual->sections)->not->toBeEmpty();
});
