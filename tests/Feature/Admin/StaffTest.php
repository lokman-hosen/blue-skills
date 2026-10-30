<?php

use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Mail\StaffInvitationMail;
use App\Domain\Identity\Models\StaffInvitation;
use App\Domain\Identity\Models\User;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([LanguageSeeder::class, RolesAndPermissionsSeeder::class]);
    $this->superuser = User::factory()->staff()->withTwoFactor()->create();
    $this->superuser->assignStaffRole(StaffRole::Superuser);
});

function staffMember(StaffRole $role, bool $twoFactor = true): User
{
    $factory = User::factory()->staff();
    $user = ($twoFactor ? $factory->withTwoFactor() : $factory)->create();

    return $user->assignStaffRole($role);
}

it('requires confirmed 2FA before any staff screen', function () {
    $staff = staffMember(StaffRole::Superuser, twoFactor: false);

    foreach ([route('dashboard'), route('admin.staff.index'), route('design.index')] as $url) {
        $this->actingAs($staff)->get($url)->assertRedirect(route('security.edit'));
    }

    $this->actingAs($this->superuser)->get(route('admin.staff.index'))->assertOk();
});

it('can be relaxed on a local machine only through config', function () {
    config(['blueskills.enforce_staff_two_factor' => false]);

    $this->actingAs(staffMember(StaffRole::Superuser, twoFactor: false))->get(route('admin.staff.index'))->assertOk();
});

it('lists staff to superusers only', function () {
    $this->actingAs($this->superuser)->get(route('admin.staff.index'))
        ->assertInertia(fn (Assert $page) => $page->component('admin/staff')->has('members', 1)->has('roles', 5));

    $this->actingAs(staffMember(StaffRole::VerificationOfficer))->get(route('admin.staff.index'))->assertForbidden();
    $this->actingAs(User::factory()->create())->get(route('admin.staff.index'))->assertForbidden();
    $this->actingAs(User::factory()->worker()->create())->get(route('admin.staff.index'))->assertForbidden();
});

it('invites staff by email and lets them set up their account', function () {
    Mail::fake();

    $this->actingAs($this->superuser)
        ->post(route('admin.staff.invitations.store'), ['email' => 'Officer@blueskills.test', 'role' => 'verification_officer'])
        ->assertRedirect();

    $token = null;
    Mail::assertQueued(StaffInvitationMail::class, function (StaffInvitationMail $mail) use (&$token) {
        $token = $mail->token;

        return $mail->hasTo('officer@blueskills.test');
    });
    auth()->logout();

    $this->get(route('staff-invitations.show', $token))->assertInertia(fn (Assert $page) => $page
        ->component('onboarding/staff-invitation')
        ->where('invitation.role', 'verification_officer'));

    $this->post(route('staff-invitations.store', $token), [
        'name' => 'Sadia Rahman',
        'password' => 'correct-horse-battery',
        'password_confirmation' => 'correct-horse-battery',
    ])->assertRedirect(route('security.edit'));

    $officer = User::query()->where('email', 'officer@blueskills.test')->firstOrFail();

    $this->assertAuthenticatedAs($officer);
    expect($officer->isStaff())->toBeTrue()
        ->and($officer->email_verified_at)->not->toBeNull()
        ->and($officer->hasStaffRole(StaffRole::VerificationOfficer))->toBeTrue()
        ->and(StaffInvitation::query()->firstOrFail()->accepted_at)->not->toBeNull();

    // The link works once.
    auth()->logout();
    $this->post(route('staff-invitations.store', $token), [
        'name' => 'Again', 'password' => 'correct-horse-battery', 'password_confirmation' => 'correct-horse-battery',
    ])->assertStatus(410);
});

it('refuses to invite an address that already has an account', function () {
    Mail::fake();
    User::factory()->create(['email' => 'taken@abc.test']);

    $this->actingAs($this->superuser)
        ->post(route('admin.staff.invitations.store'), ['email' => 'TAKEN@abc.test', 'role' => 'crm_agent'])
        ->assertSessionHasErrors('email');

    Mail::assertNothingQueued();
});

it('lets only superusers invite', function () {
    $this->actingAs(staffMember(StaffRole::TrustOfficer))
        ->post(route('admin.staff.invitations.store'), ['email' => 'x@blueskills.test', 'role' => 'crm_agent'])
        ->assertForbidden();
});

it('changes staff roles but keeps one superuser', function () {
    $officer = staffMember(StaffRole::CrmAgent);

    $this->actingAs($this->superuser)
        ->patch(route('admin.staff.members.update', $officer), ['role' => 'finance_officer'])
        ->assertRedirect();
    expect($officer->hasStaffRole(StaffRole::FinanceOfficer))->toBeTrue();

    $this->actingAs($this->superuser)
        ->patch(route('admin.staff.members.update', $this->superuser), ['role' => 'crm_agent'])
        ->assertSessionHasErrors('role');
    expect($this->superuser->hasStaffRole(StaffRole::Superuser))->toBeTrue();
});

it('deactivates staff who then cannot sign in', function () {
    $officer = staffMember(StaffRole::CrmAgent);
    $officer->forceFill(['email' => 'crm@blueskills.test', 'password' => 'password'])->save();

    $this->actingAs($this->superuser)->delete(route('admin.staff.members.destroy', $officer))->assertRedirect();
    $this->assertSoftDeleted($officer);

    auth()->logout();
    $this->post(route('login.store'), ['email' => 'crm@blueskills.test', 'password' => 'password']);
    $this->assertGuest();
});

it('cannot deactivate yourself or touch non-staff accounts', function () {
    $this->actingAs($this->superuser)
        ->delete(route('admin.staff.members.destroy', $this->superuser))
        ->assertSessionHasErrors('member');

    $this->actingAs($this->superuser)
        ->delete(route('admin.staff.members.destroy', User::factory()->create()))
        ->assertNotFound();
});

it('keeps Horizon behind staff 2FA', function () {
    expect(Gate::forUser($this->superuser)->allows('viewHorizon'))->toBeTrue()
        ->and(Gate::forUser(staffMember(StaffRole::Superuser, twoFactor: false))->allows('viewHorizon'))->toBeFalse();
});

it('resets a staff member\'s two-factor so they set it up again at the next sign-in', function () {
    $officer = staffMember(StaffRole::VerificationOfficer);
    $officer->passkeys()->create(['name' => 'lost phone', 'credential_id' => 'cred-1', 'credential' => ['id' => 'cred-1']]);

    $this->actingAs($this->superuser)
        ->post(route('admin.staff.members.two-factor.reset', $officer))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $officer->refresh();
    expect($officer->two_factor_secret)->toBeNull()
        ->and($officer->two_factor_recovery_codes)->toBeNull()
        ->and($officer->two_factor_confirmed_at)->toBeNull()
        ->and($officer->passkeys()->count())->toBe(0);
    $this->assertDatabaseHas('activity_log', [
        'description' => 'staff.two_factor_reset',
        'subject_id' => $officer->id,
        'causer_id' => $this->superuser->id,
    ]);

    // Staff screens stay closed until they set it up again.
    $this->actingAs($officer)->get(route('admin.evidence.index'))->assertRedirect(route('security.edit'));
});

it('lets only superusers reset two-factor, never their own or a non-staff account', function () {
    $officer = staffMember(StaffRole::TrustOfficer);

    $this->actingAs(staffMember(StaffRole::VerificationOfficer))
        ->post(route('admin.staff.members.two-factor.reset', $officer))
        ->assertForbidden();
    expect($officer->fresh()->two_factor_confirmed_at)->not->toBeNull();

    $this->actingAs($this->superuser)
        ->post(route('admin.staff.members.two-factor.reset', $this->superuser))
        ->assertSessionHasErrors('member');

    $this->actingAs($this->superuser)
        ->post(route('admin.staff.members.two-factor.reset', staffMember(StaffRole::CrmAgent, twoFactor: false)))
        ->assertSessionHasErrors('member');

    $this->actingAs($this->superuser)
        ->post(route('admin.staff.members.two-factor.reset', User::factory()->create()))
        ->assertNotFound();
});
