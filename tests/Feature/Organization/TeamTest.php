<?php

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Models\User;
use App\Domain\Organization\Mail\OrganizationInvitationMail;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationInvitation;
use App\Domain\Organization\Models\OrganizationMember;
use App\Domain\Organization\Support\CurrentOrganization;
use App\Domain\Organization\Support\OrganizationRoles;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed([LanguageSeeder::class, RolesAndPermissionsSeeder::class]);
    $this->organization = Organization::factory()->employer()->verified()->create();
    $this->owner = memberWithRole($this->organization, OrganizationRole::Owner);
});

function memberWithRole(Organization $organization, OrganizationRole $role, array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $organization->users()->attach($user->id, ['joined_at' => now()]);
    app(OrganizationRoles::class)->assign($user, $organization, $role);

    return $user;
}

function membership(Organization $organization, User $user): OrganizationMember
{
    return $organization->members()->where('user_id', $user->id)->firstOrFail();
}

function roleOf(Organization $organization, User $user): ?OrganizationRole
{
    return app(OrganizationRoles::class)->roleOf($user, $organization);
}

it('lists members and roles to any member', function () {
    $viewer = memberWithRole($this->organization, OrganizationRole::Viewer);

    $this->actingAs($viewer)->get(route('team.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('org/team')
            ->has('members', 2)
            ->where('canManage', false)
            ->where('roles', ['owner', 'hr_admin', 'recruiter', 'hiring_manager', 'viewer']));

    $this->actingAs($this->owner)->get(route('team.index'))->assertInertia(fn (Assert $page) => $page->where('canManage', true));
});

it('keeps workers and staff out of team pages', function (string $state) {
    $this->actingAs(User::factory()->{$state}()->create())->get(route('team.index'))->assertForbidden();
})->with(['worker', 'staff']);

it('emails a single-use invitation', function () {
    Mail::fake();

    $this->actingAs($this->owner)
        ->post(route('team.invitations.store'), ['email' => 'New.Recruiter@abc.test', 'role' => 'recruiter'])
        ->assertRedirect();

    $invitation = OrganizationInvitation::query()->firstOrFail();

    expect($invitation->email)->toBe('new.recruiter@abc.test')
        ->and($invitation->role)->toBe(OrganizationRole::Recruiter->value)
        ->and($invitation->expires_at->isAfter(now()->addDays(6)))->toBeTrue();

    Mail::assertQueued(OrganizationInvitationMail::class, function (OrganizationInvitationMail $mail) use ($invitation) {
        return $mail->hasTo('new.recruiter@abc.test')
            && OrganizationInvitation::hashToken($mail->token) === $invitation->token_hash;
    });
});

it('replaces an earlier pending invitation for the same address', function () {
    Mail::fake();
    $this->actingAs($this->owner);
    $this->post(route('team.invitations.store'), ['email' => 'a@abc.test', 'role' => 'viewer']);
    $this->post(route('team.invitations.store'), ['email' => 'a@abc.test', 'role' => 'recruiter']);

    expect(OrganizationInvitation::query()->whereNull('revoked_at')->pluck('role')->all())->toBe([OrganizationRole::Recruiter->value]);
});

it('validates invitations', function () {
    Mail::fake();
    memberWithRole($this->organization, OrganizationRole::Recruiter, ['email' => 'existing@abc.test']);
    $this->actingAs($this->owner);

    $this->post(route('team.invitations.store'), ['email' => 'not-an-email', 'role' => 'viewer'])->assertSessionHasErrors('email');
    $this->post(route('team.invitations.store'), ['email' => 'x@abc.test', 'role' => 'case_officer'])->assertSessionHasErrors('role');
    $this->post(route('team.invitations.store'), ['email' => 'EXISTING@abc.test', 'role' => 'viewer'])->assertSessionHasErrors('email');

    Mail::assertNothingQueued();
});

it('lets only team managers invite', function () {
    Mail::fake();
    $recruiter = memberWithRole($this->organization, OrganizationRole::Recruiter);

    $this->actingAs($recruiter)
        ->post(route('team.invitations.store'), ['email' => 'x@abc.test', 'role' => 'viewer'])
        ->assertForbidden();
});

it('shows the invitation link to guests and sends them back to it after sign-in', function () {
    Mail::fake();
    $this->actingAs($this->owner)->post(route('team.invitations.store'), ['email' => 'invitee@abc.test', 'role' => 'recruiter']);
    $token = Mail::queued(OrganizationInvitationMail::class)->first()->token;
    auth()->logout();

    $this->get(route('invitations.show', $token))
        ->assertOk()
        ->assertSessionHas('url.intended', route('invitations.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('onboarding/invitation')
            ->where('invitation.organization.displayName', $this->organization->display_name));

    $this->get(route('invitations.show', str_repeat('a', 48)))
        ->assertInertia(fn (Assert $page) => $page->where('invitation', null));
});

it('joins the organization when the invited address accepts', function () {
    $invitation = OrganizationInvitation::factory()->for($this->organization)->create(['email' => 'invitee@abc.test', 'role' => OrganizationRole::Recruiter]);
    $invitee = User::factory()->create(['email' => 'Invitee@abc.test']);

    $this->actingAs($invitee)->get(route('dashboard'))->assertRedirect(route('invitations.index'));
    $this->actingAs($invitee)->get(route('invitations.index'))->assertInertia(fn (Assert $page) => $page->has('invitations', 1));

    $this->actingAs($invitee)
        ->post(route('invitations.accept', $invitation))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas(CurrentOrganization::SESSION_KEY, $this->organization->id);

    expect(roleOf($this->organization, $invitee))->toBe(OrganizationRole::Recruiter)
        ->and($invitation->fresh()?->accepted_at)->not->toBeNull()
        ->and(membership($this->organization, $invitee)->invited_by)->toBe($invitation->invited_by);
});

it('refuses invitations for another address, expired or revoked ones', function (array $state, string $email) {
    $invitation = OrganizationInvitation::factory()->for($this->organization)->create(['email' => 'invitee@abc.test', ...$state]);

    $this->actingAs(User::factory()->create(['email' => $email]))
        ->post(route('invitations.accept', $invitation))
        ->assertForbidden();
})->with([
    'other address' => [[], 'someone@else.test'],
    'expired' => [['expires_at' => now()->subDay()], 'invitee@abc.test'],
    'revoked' => [['revoked_at' => now()], 'invitee@abc.test'],
]);

it('changes a member role and logs it', function () {
    $member = memberWithRole($this->organization, OrganizationRole::Recruiter);

    $this->actingAs($this->owner)
        ->patch(route('team.members.update', membership($this->organization, $member)), ['role' => 'hr_admin'])
        ->assertRedirect();

    expect(roleOf($this->organization, $member))->toBe(OrganizationRole::HrAdmin)
        ->and(Activity::query()->where('description', 'member.role_changed')->first()?->properties->all())
        ->toMatchArray(['old' => 'recruiter', 'new' => 'hr_admin']);
});

it('never leaves an organization without an owner', function () {
    $this->actingAs($this->owner)
        ->patch(route('team.members.update', membership($this->organization, $this->owner)), ['role' => 'viewer'])
        ->assertSessionHasErrors('role');

    $hr = memberWithRole($this->organization, OrganizationRole::HrAdmin);
    $this->actingAs($hr)
        ->delete(route('team.members.destroy', membership($this->organization, $this->owner)))
        ->assertSessionHasErrors('member');

    expect(roleOf($this->organization, $this->owner))->toBe(OrganizationRole::Owner);
});

it('removes a member and their roles', function () {
    $member = memberWithRole($this->organization, OrganizationRole::Viewer);

    $this->actingAs($this->owner)->delete(route('team.members.destroy', membership($this->organization, $member)))->assertRedirect();

    expect($this->organization->users()->whereKey($member->id)->exists())->toBeFalse()
        ->and(roleOf($this->organization, $member))->toBeNull();
});

it('does not touch members of other organizations', function () {
    $other = Organization::factory()->create();
    $stranger = memberWithRole($other, OrganizationRole::Viewer);

    $this->actingAs($this->owner)
        ->delete(route('team.members.destroy', membership($other, $stranger)))
        ->assertNotFound();
});

it('lets managers cancel pending invitations', function () {
    $invitation = OrganizationInvitation::factory()->for($this->organization)->create();
    $viewer = memberWithRole($this->organization, OrganizationRole::Viewer);

    $this->actingAs($viewer)->delete(route('team.invitations.destroy', $invitation))->assertForbidden();
    $this->actingAs($this->owner)->delete(route('team.invitations.destroy', $invitation))->assertRedirect();

    expect($invitation->fresh()?->revoked_at)->not->toBeNull();
});

it('renders the invitation email with the accept link', function () {
    $invitation = OrganizationInvitation::factory()->for($this->organization)->create(['invited_by' => $this->owner->id, 'role' => OrganizationRole::Recruiter]);
    $token = str_repeat('b', 48);

    $html = (new OrganizationInvitationMail($invitation, $token))->render();

    expect($html)->toContain(route('invitations.show', $token))
        ->toContain($this->organization->display_name)
        ->toContain('Recruiter');
});

it('shares what the member may do in the current organization', function () {
    $hr = memberWithRole($this->organization, OrganizationRole::HrAdmin);

    $this->actingAs($hr)->get(route('team.index'))->assertInertia(fn (Assert $page) => $page
        ->where('organizationAbilities.update', false)
        ->where('organizationAbilities.manageTeam', true));

    $this->actingAs($this->owner)->get(route('team.index'))->assertInertia(fn (Assert $page) => $page
        ->where('organizationAbilities.update', true));
});
