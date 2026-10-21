<?php

use App\Domain\Applications\Enums\InvitationStatus;
use App\Domain\Applications\Models\JobInvitation;
use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Organization\Models\Organization;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Worker\Actions\UpdateProfileVisibility;
use App\Domain\Worker\Enums\ProfileVisibility;
use Database\Seeders\CountrySeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TaxonomySeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, LanguageSeeder::class, TaxonomySeeder::class, RolesAndPermissionsSeeder::class]);
    $this->employer = Organization::factory()->employer()->verified()->create(['display_name' => 'ABC Contracting']);
    $this->recruiter = orgMember($this->employer, OrganizationRole::Recruiter);
    $electrician = Occupation::query()->where('slug', 'electrician')->value('id');
    $this->job = JobPosting::factory()->published()->create(['organization_id' => $this->employer->id, 'employer_organization_id' => $this->employer->id, 'occupation_id' => $electrician, 'title' => 'Maintenance Electrician']);
    $this->worker = makeWorker(['occupation_id' => $electrician]);
    app(UpdateProfileVisibility::class)->handle($this->worker->workerProfile, ProfileVisibility::EmployersAndAgencies);
});

function invite(User $member, Organization $organization, User $worker, string $job, array $extra = [])
{
    return actingInOrg($member, $organization)->post(route('org.talent.invite', $worker->workerProfile->reference), ['job' => $job, ...$extra]);
}

it('invites a findable worker once per job and tells them', function () {
    invite($this->recruiter, $this->employer, $this->worker, $this->job->public_code, ['note' => 'Your DB installation work fits this team.'])
        ->assertSessionHasNoErrors();

    $invitation = JobInvitation::query()->sole();
    expect($invitation->status)->toBe(InvitationStatus::Sent)
        ->and($this->worker->notifications()->sole()->type)->toBe('job_invitation');

    invite($this->recruiter, $this->employer, $this->worker, $this->job->public_code)
        ->assertSessionHasErrors(['job' => __('invitations.already_invited')]);

    $this->actingAs($this->worker)->get(route('worker.applications.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('invitations', 1)
            ->where('invitations.0.organization', 'ABC Contracting')
            ->where('invitations.0.note', 'Your DB installation work fits this team.'));
});

it('marks the invitation applied when the worker applies, or declined', function () {
    invite($this->recruiter, $this->employer, $this->worker, $this->job->public_code);

    $this->actingAs($this->worker)->post(route('worker.apply.store', $this->job), ['items' => []])->assertSessionHasNoErrors();
    expect(JobInvitation::query()->sole()->status)->toBe(InvitationStatus::Applied);

    $other = JobPosting::factory()->published()->create(['organization_id' => $this->employer->id, 'employer_organization_id' => $this->employer->id]);
    invite($this->recruiter, $this->employer, $this->worker, $other->public_code);
    $invitation = JobInvitation::query()->where('job_id', $other->id)->sole();

    $this->actingAs(makeWorker())->post(route('worker.applications.invitations.decline', $invitation))->assertForbidden();
    $this->actingAs($this->worker)->post(route('worker.applications.invitations.decline', $invitation))->assertRedirect();
    expect($invitation->fresh()?->status)->toBe(InvitationStatus::Declined);

    $this->post(route('worker.applications.invitations.decline', $invitation))->assertSessionHasErrors('invitation');
});

it('refuses hidden workers, closed or foreign jobs, applicants and the daily cap', function () {
    $hidden = makeWorker(['occupation_id' => $this->job->occupation_id]);
    invite($this->recruiter, $this->employer, $hidden, $this->job->public_code)->assertSessionHasErrors('job');

    $closed = JobPosting::factory()->published()->create(['organization_id' => $this->employer->id, 'employer_organization_id' => $this->employer->id, 'deadline' => now()->subDay()]);
    invite($this->recruiter, $this->employer, $this->worker, $closed->public_code)->assertSessionHasErrors(['job' => __('invitations.job_not_open')]);

    $foreign = JobPosting::factory()->published()->create();
    invite($this->recruiter, $this->employer, $this->worker, $foreign->public_code)->assertStatus(422);

    JobInvitation::factory()->count(JobInvitation::DAILY_LIMIT)->create(['organization_id' => $this->employer->id]);
    invite($this->recruiter, $this->employer, $this->worker, $this->job->public_code)
        ->assertSessionHasErrors(['job' => __('invitations.daily_limit', ['limit' => JobInvitation::DAILY_LIMIT])]);

    expect(JobInvitation::query()->where('worker_profile_id', $this->worker->workerProfile->id)->count())->toBe(0);
});

it('lets only members who search talent invite', function () {
    invite(orgMember($this->employer, OrganizationRole::Viewer), $this->employer, $this->worker, $this->job->public_code)->assertForbidden();

    expect(JobInvitation::query()->count())->toBe(0);
});
