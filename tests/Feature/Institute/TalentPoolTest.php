<?php

use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\JobInvitation;
use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Models\User;
use App\Domain\Institute\Models\Cohort;
use App\Domain\Institute\Models\CohortMember;
use App\Domain\Institute\Models\Course;
use App\Domain\Institute\Models\TalentPool;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\States\Verification\Pending;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Worker\Actions\UpdateProfileVisibility;
use App\Domain\Worker\Enums\ProfileVisibility;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, RolesAndPermissionsSeeder::class]);
    $this->institute = Organization::factory()->institute()->verified()->create(['display_name' => 'Meghna TTC']);
    $this->owner = orgMember($this->institute, OrganizationRole::InstituteOwner);
    $this->cohort = Cohort::factory()->create([
        'course_id' => Course::factory()->create(['organization_id' => $this->institute->id]),
        'organization_id' => $this->institute->id,
    ]);
    $this->occupation = Occupation::factory()->create();
});

/** A graduate who accepted the cohort; `findable` makes them visible to employers. */
function graduate(bool $findable = true, bool $sharesProgress = false): User
{
    $worker = makeWorker(['occupation_id' => test()->occupation->id]);

    if ($findable) {
        app(UpdateProfileVisibility::class)->handle($worker->workerProfile, ProfileVisibility::EmployersAndAgencies);
    }

    CohortMember::factory()->accepted($worker->workerProfile, $sharesProgress)->create(['cohort_id' => test()->cohort->id]);

    return $worker;
}

it('lets the institute pool its own graduates and publish the pool', function () {
    $findable = graduate();
    $private = graduate(findable: false);
    $stranger = makeWorker();

    actingInOrg($this->owner, $this->institute)->post(route('org.pools.store'), ['name' => ''])->assertSessionHasErrors('name');
    $this->post(route('org.pools.store'), ['name' => 'Electricians ready to travel', 'occupation_id' => $this->occupation->id]);
    $pool = TalentPool::query()->sole();

    $this->get(route('org.pools.show', $pool))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('org/pools/show')->has('members', 0)->has('available', 2));

    $this->post(route('org.pools.members.store', $pool), ['profiles' => [$stranger->workerProfile->id]])->assertSessionHasErrors('profiles');
    $this->post(route('org.pools.members.store', $pool), ['profiles' => [$findable->workerProfile->id, $private->workerProfile->id]])->assertRedirect();

    $this->get(route('org.pools.show', $pool))->assertInertia(fn (Assert $page) => $page
        ->where('pool.members', 2)
        ->where('pool.visibleMembers', 1)
        ->has('available', 0));

    $this->post(route('org.pools.publish', [$pool, 'publish']))->assertRedirect();
    expect($pool->fresh()?->isPublished())->toBeTrue();

    $this->delete(route('org.pools.members.destroy', [$pool, $private->workerProfile]))->assertRedirect();
    expect($pool->workers()->count())->toBe(1);

    // Trainers don't keep pools; an unverified institute can't publish.
    actingInOrg(orgMember($this->institute, OrganizationRole::Trainer), $this->institute)->get(route('org.pools.index'))->assertForbidden();
    $this->institute->update(['verification_status' => Pending::class]);
    actingInOrg($this->owner, $this->institute)->post(route('org.pools.publish', [$pool->fresh(), 'unpublish']));
    $this->post(route('org.pools.publish', [$pool, 'publish']))->assertSessionHasErrors('pool');
});

it('shows published pools to verified employers and agencies, with only findable graduates', function () {
    $findable = graduate();
    $private = graduate(findable: false);
    $published = TalentPool::factory()->published()->create(['organization_id' => $this->institute->id, 'name' => 'Ready to travel']);
    $published->workers()->attach([$findable->workerProfile->id, $private->workerProfile->id]);
    TalentPool::factory()->create(['organization_id' => $this->institute->id, 'name' => 'Private list']);

    $employer = Organization::factory()->employer()->verified()->create();
    $recruiter = orgMember($employer, OrganizationRole::Recruiter);

    actingInOrg($recruiter, $employer)->get(route('org.institute-pools.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('pools', 1)->where('pools.0.name', 'Ready to travel')->where('pools.0.institute', 'Meghna TTC'));
    $this->get(route('org.institute-pools.show', $published))
        ->assertInertia(fn (Assert $page) => $page->has('workers', 1)->where('workers.0.reference', $findable->workerProfile->reference));
    $this->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page->where('organizationAbilities.browsePools', true));

    $private = TalentPool::query()->where('name', 'Private list')->sole();
    $this->get(route('org.institute-pools.show', $private))->assertForbidden();

    $agency = Organization::factory()->agency()->verified()->create();
    actingInOrg(orgMember($agency, OrganizationRole::CaseOfficer), $agency)->get(route('org.institute-pools.index'))->assertOk();

    $unverified = Organization::factory()->employer()->create();
    actingInOrg(orgMember($unverified, OrganizationRole::Owner), $unverified)->get(route('org.institute-pools.index'))->assertForbidden();
    actingInOrg($this->owner, $this->institute)->get(route('org.institute-pools.index'))->assertForbidden();
});

it('shows employer interest and applications only for graduates who share their progress', function () {
    $sharing = graduate(sharesProgress: true);
    $quiet = graduate();
    $application = Application::factory()->create(['worker_profile_id' => $sharing->workerProfile->id]);
    JobInvitation::factory()->create(['worker_profile_id' => $sharing->workerProfile->id]);
    Application::factory()->create(['worker_profile_id' => $quiet->workerProfile->id]);

    $placement = orgMember($this->institute, OrganizationRole::PlacementOfficer);
    actingInOrg($placement, $this->institute)->get(route('org.placements.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('org/placements')
            ->has('placements', 1)
            ->where('placements.0.name', $sharing->name)
            ->where('placements.0.applications.0.jobCode', $application->job->public_code)
            ->has('placements.0.interest', 1)
            ->where('counts.sharing', 1)
            ->where('counts.applied', 1));

    actingInOrg(orgMember($this->institute, OrganizationRole::Trainer), $this->institute)->get(route('org.placements.index'))->assertForbidden();
});
