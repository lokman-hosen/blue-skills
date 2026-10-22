<?php

use App\Domain\Config\Models\Country;
use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\PilotInvite;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, LanguageSeeder::class, RolesAndPermissionsSeeder::class]);
});

function pilotRegistration(array $overrides = []): array
{
    return [
        'type' => 'employer',
        'legal_name' => 'Harbour Lights Facilities LLC',
        'display_name' => 'Harbour Lights Facilities',
        'country_code' => 'AE',
        'city' => 'Dubai',
        'registration_number' => 'DED-7700112',
        ...$overrides,
    ];
}

it('needs no code while the pilot is off', function () {
    $this->actingAs(User::factory()->create())->post(route('organizations.store'), pilotRegistration())
        ->assertRedirect(route('organizations.verification.edit'));
});

it('registers an organization with a pilot code and uses up one place', function () {
    config(['blueskills.pilot.enabled' => true]);
    $invite = PilotInvite::factory()->create(['code' => 'PILOT-7KM3QX', 'max_uses' => 2, 'organization_type' => OrganizationType::Employer]);

    $this->actingAs(User::factory()->create())
        ->post(route('organizations.store'), pilotRegistration(['pilot_code' => ' pilot-7km3qx ']))
        ->assertRedirect(route('organizations.verification.edit'));

    $organization = Organization::query()->where('display_name', 'Harbour Lights Facilities')->sole();
    expect($organization->pilot_invite_id)->toBe($invite->id)
        ->and($organization->verification_status->getValue())->toBe('pending')
        ->and($invite->fresh()->used_count)->toBe(1);
});

it('refuses missing, unknown, used-up, expired, revoked and wrong-type codes', function (?string $code, string $error) {
    config(['blueskills.pilot.enabled' => true]);
    PilotInvite::factory()->create(['code' => 'PILOT-USED22', 'max_uses' => 1, 'used_count' => 1]);
    PilotInvite::factory()->create(['code' => 'PILOT-OLD333', 'expires_at' => now()->subDay()]);
    PilotInvite::factory()->create(['code' => 'PILOT-REV444', 'revoked_at' => now()]);
    PilotInvite::factory()->create(['code' => 'PILOT-AGN555', 'organization_type' => OrganizationType::Agency]);

    $this->actingAs(User::factory()->create())
        ->post(route('organizations.store'), pilotRegistration(['pilot_code' => $code]))
        ->assertSessionHasErrors(['pilot_code' => str_starts_with($error, 'pilot.') ? __($error) : $error]);

    expect(Organization::query()->count())->toBe(0);
})->with([
    'missing' => [null, 'The pilot code field is required.'],
    'unknown' => ['PILOT-NOPE00', 'pilot.errors.invalid'],
    'used up' => ['PILOT-USED22', 'pilot.errors.invalid'],
    'expired' => ['PILOT-OLD333', 'pilot.errors.invalid'],
    'revoked' => ['PILOT-REV444', 'pilot.errors.invalid'],
    'wrong type' => ['PILOT-AGN555', 'pilot.errors.wrong_type'],
]);

it('shows the pilot notice and the code field while the pilot is on', function () {
    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->where('pilot', false));

    config(['blueskills.pilot.enabled' => true]);
    $this->get(route('home'))->assertInertia(fn (Assert $page) => $page->where('pilot', true));
});

it('lets organization reviewers issue and revoke codes', function () {
    config(['blueskills.pilot.enabled' => true]);
    $officer = staffUser(StaffRole::VerificationOfficer);

    $this->actingAs($officer)->post(route('admin.pilot.store'), [
        'partner_name' => 'Harbour Lights Facilities',
        'organization_type' => 'employer',
        'max_uses' => 3,
        'expires_on' => now()->addMonth()->toDateString(),
    ])->assertRedirect(route('admin.pilot.index'));

    $invite = PilotInvite::query()->sole();
    expect($invite->code)->toMatch('/^PILOT-[A-HJKMNP-Z2-9]{6}$/')
        ->and($invite->max_uses)->toBe(3)
        ->and($invite->created_by)->toBe($officer->id);

    $this->actingAs($officer)->get(route('admin.pilot.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/pilot')
            ->where('pilotEnabled', true)
            ->where('invites.0.code', $invite->code)
            ->where('invites.0.status.value', 'active'));

    $this->actingAs($officer)->post(route('admin.pilot.revoke', $invite))->assertRedirect(route('admin.pilot.index'));
    expect($invite->fresh()->state())->toBe('revoked');
});

it('keeps pilot codes from staff who do not review organizations and from organizations', function () {
    $invite = PilotInvite::factory()->create();

    $this->actingAs(staffUser(StaffRole::CrmAgent))->get(route('admin.pilot.index'))->assertForbidden();
    $this->actingAs(staffUser(StaffRole::FinanceOfficer))->post(route('admin.pilot.store'), ['partner_name' => 'X', 'max_uses' => 1])->assertForbidden();
    $this->actingAs(staffUser(StaffRole::CrmAgent))->post(route('admin.pilot.revoke', $invite))->assertForbidden();
    $this->actingAs(User::factory()->create())->get(route('admin.pilot.index'))->assertForbidden();
});

it('validates new codes', function () {
    $this->actingAs(staffUser(StaffRole::VerificationOfficer))
        ->post(route('admin.pilot.store'), ['partner_name' => '', 'organization_type' => 'bank', 'max_uses' => 0, 'expires_on' => '2020-01-01'])
        ->assertSessionHasErrors(['partner_name', 'organization_type', 'max_uses', 'expires_on']);
});

it('opens only the pilot destinations on a new database and never undoes staff changes', function () {
    Country::query()->delete();
    config(['blueskills.pilot.enabled' => true]);

    $this->seed(CountrySeeder::class);
    expect(Country::query()->where('enabled_as_destination', true)->pluck('code')->all())->toBe(['AE']);

    Country::query()->where('code', 'SA')->update(['enabled_as_destination' => true]);
    $this->seed(CountrySeeder::class);
    expect(Country::query()->where('enabled_as_destination', true)->orderBy('code')->pluck('code')->all())->toBe(['AE', 'SA']);
});
