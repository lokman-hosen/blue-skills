<?php

use App\Domain\Identity\Enums\StaffRole;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('lists recorded changes for superusers, filtered by area and person', function () {
    $superuser = staffUser(StaffRole::Superuser);
    activity('trust')->causedBy($superuser)->withProperties(['reason' => 'fake_job'])->log('trust.suspend');
    activity('config')->log('country.updated');

    $this->actingAs($superuser)->get(route('admin.audit.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/audit')->has('entries.data', 2)->where('areas', ['config', 'trust']));
    $this->get(route('admin.audit.index', ['area' => 'trust']))
        ->assertInertia(fn (Assert $page) => $page->has('entries.data', 1)->where('entries.data.0.actor', $superuser->name));
    $this->get(route('admin.audit.index', ['actor' => 'nobody-matches']))
        ->assertInertia(fn (Assert $page) => $page->has('entries.data', 0));

    $this->actingAs(staffUser(StaffRole::TrustOfficer))->get(route('admin.audit.index'))->assertForbidden();
});
