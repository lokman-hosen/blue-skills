<?php

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Institute\Enums\CohortMemberStatus;
use App\Domain\Institute\Enums\CohortStatus;
use App\Domain\Institute\Models\Cohort;
use App\Domain\Institute\Models\CohortMember;
use App\Domain\Institute\Models\Course;
use App\Domain\Institute\Models\TalentPool;
use App\Domain\Organization\Models\Organization;
use App\Http\Controllers\Public\CohortInviteController;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([CountrySeeder::class, RolesAndPermissionsSeeder::class]);
    $this->institute = Organization::factory()->institute()->verified()->create(['display_name' => 'Meghna TTC', 'country_code' => 'BD']);
    $this->owner = orgMember($this->institute, OrganizationRole::InstituteOwner);
    $this->placement = orgMember($this->institute, OrganizationRole::PlacementOfficer);
    $this->course = Course::factory()->create(['organization_id' => $this->institute->id, 'title' => 'Electrical Installation']);
    $this->cohort = Cohort::factory()->create(['course_id' => $this->course->id, 'organization_id' => $this->institute->id]);
});

it('lets the institute add courses and cohorts with an invite link and QR code', function () {
    actingInOrg($this->owner, $this->institute)
        ->post(route('org.courses.store'), ['title' => ''])
        ->assertSessionHasErrors('title');
    $this->post(route('org.courses.store'), ['title' => 'Welding', 'duration_weeks' => 12])->assertRedirect(route('org.courses.index'));
    $welding = Course::query()->where('title', 'Welding')->sole();

    $this->post(route('org.cohorts.store', $welding), ['name' => '2026 Cohort', 'starts_on' => '2026-02-01', 'ends_on' => '2026-01-01'])
        ->assertSessionHasErrors('ends_on');
    $this->post(route('org.cohorts.store', $welding), ['name' => '2026 Cohort', 'starts_on' => '2026-01-01', 'ends_on' => '2026-06-30']);
    $cohort = $welding->cohorts()->sole();

    $this->get(route('org.cohorts.show', $cohort))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('org/cohorts/show')
            ->where('cohort.inviteUrl', route('cohort-invite.show', $cohort->invite_code))
            ->where('cohort.qrSvg', fn (string $svg) => str_starts_with($svg, '<svg'))
            ->where('cohort.whatsappText', fn (string $text) => str_contains($text, $cohort->invite_code))
            ->where('abilities.manage', true));
    $this->get(route('org.cohorts.qr', $cohort))->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
    $this->get(route('org.courses.index'))->assertInertia(fn (Assert $page) => $page->has('courses', 2)->where('canManage', true));

    // Placement officers run cohorts but don't manage courses; other organizations see nothing.
    actingInOrg($this->placement, $this->institute)->post(route('org.courses.store'), ['title' => 'Plumbing'])->assertForbidden();
    $this->get(route('org.cohorts.show', $cohort))->assertInertia(fn (Assert $page) => $page->where('abilities.manage', true)->where('abilities.invite', true));
    $other = Organization::factory()->institute()->verified()->create();
    actingInOrg(orgMember($other, OrganizationRole::InstituteOwner), $other)->get(route('org.cohorts.show', $cohort))->assertNotFound();
    $employer = Organization::factory()->employer()->verified()->create();
    actingInOrg(orgMember($employer, OrganizationRole::Owner), $employer)->get(route('org.courses.index'))->assertForbidden();
});

it('keeps an optional roster that only tracks who joined', function () {
    actingInOrg($this->placement, $this->institute)
        ->post(route('org.cohorts.roster', $this->cohort), ['roster' => "Rahim Uddin, 01711000001\nno phone here"])
        ->assertSessionHasErrors('roster');
    $this->post(route('org.cohorts.roster', $this->cohort), ['roster' => "Rahim Uddin, 01711000001\nKarim Ahmed; +8801711000002\nRahim again, +8801711000001"])
        ->assertRedirect();

    expect($this->cohort->members()->pluck('roster_phone')->sort()->values()->all())->toBe(['+8801711000001', '+8801711000002'])
        ->and($this->cohort->members()->whereNotNull('worker_profile_id')->count())->toBe(0);

    $karim = $this->cohort->members()->where('roster_phone', '+8801711000002')->sole();
    $this->delete(route('org.cohorts.members.destroy', [$this->cohort, $karim]))->assertRedirect();
    expect($this->cohort->members()->count())->toBe(1);

    $joined = CohortMember::factory()->accepted()->create(['cohort_id' => $this->cohort->id]);
    $this->delete(route('org.cohorts.members.destroy', [$this->cohort, $joined]))->assertSessionHasErrors('member');
});

it('matches a roster line only when the worker accepts, with sharing off unless chosen', function () {
    $worker = makeWorker();
    $worker->update(['phone' => '+8801711000009']);
    $line = CohortMember::factory()->create(['cohort_id' => $this->cohort->id, 'roster_phone' => '+8801711000009', 'roster_name' => 'Rahim U.']);

    $this->get(route('cohort-invite.show', strtolower($this->cohort->invite_code)))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('public/cohort-invite')->where('viewer', 'guest')->where('invite.institute', 'Meghna TTC'));

    $this->actingAs($worker)->get(route('cohort-invite.show', $this->cohort->invite_code))
        ->assertInertia(fn (Assert $page) => $page->where('viewer', 'worker'));
    $this->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page->where('cohortInvite.code', $this->cohort->invite_code));

    $this->post(route('cohort-invite.accept', $this->cohort->invite_code))->assertRedirect(route('worker.privacy.show'));

    $line->refresh();
    expect($line->worker_profile_id)->toBe($worker->workerProfile->id)
        ->and($line->status)->toBe(CohortMemberStatus::Accepted)
        ->and($line->shares_progress)->toBeFalse()
        ->and($this->placement->notifications()->pluck('type')->all())->toContain('graduate_joined');

    $this->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page->where('cohortInvite', null));
    $this->get(route('worker.privacy.show'))->assertInertia(fn (Assert $page) => $page->where('institutes.0.institute', 'Meghna TTC'));
    actingInOrg($this->placement, $this->institute)->get(route('org.cohorts.show', $this->cohort))
        ->assertInertia(fn (Assert $page) => $page->where('cohort.members.0.name', $worker->name)
            ->where('cohort.members.0.rosterPhone', null)
            ->where('cohort.members.0.passportReference', $worker->workerProfile->reference));
});

it('records a decline only against a roster line and refuses closed cohorts', function () {
    $worker = makeWorker();
    $worker->update(['phone' => '+8801711000010']);
    $line = CohortMember::factory()->create(['cohort_id' => $this->cohort->id, 'roster_phone' => '+8801711000010']);

    $this->actingAs($worker)->post(route('cohort-invite.decline', $this->cohort->invite_code))->assertRedirect(route('dashboard'));
    expect($line->fresh()?->status)->toBe(CohortMemberStatus::Declined)
        ->and($line->fresh()?->worker_profile_id)->toBeNull();

    $this->cohort->update(['status' => CohortStatus::Closed]);
    $this->actingAs(makeWorker())->post(route('cohort-invite.accept', $this->cohort->invite_code))->assertSessionHasErrors('cohort');

    actingInOrg($this->owner, $this->institute)->get(route('cohort-invite.show', $this->cohort->invite_code))
        ->assertInertia(fn (Assert $page) => $page->where('viewer', 'organization')->where('invite.open', false));
    $this->post(route('cohort-invite.accept', $this->cohort->invite_code))->assertForbidden();
});

it('lets the graduate change sharing or leave, which takes them out of the institute\'s pools', function () {
    $worker = makeWorker();
    $member = CohortMember::factory()->accepted($worker->workerProfile)->create(['cohort_id' => $this->cohort->id]);
    $pool = TalentPool::factory()->create(['organization_id' => $this->institute->id]);
    $pool->workers()->attach($worker->workerProfile->id);

    $this->actingAs(makeWorker())->put(route('worker.institutes.sharing', $member), ['shares_progress' => true])->assertForbidden();
    $this->actingAs($worker)->put(route('worker.institutes.sharing', $member), [])->assertSessionHasErrors('shares_progress');
    $this->put(route('worker.institutes.sharing', $member), ['shares_progress' => true])->assertRedirect();
    expect($member->fresh()?->shares_progress)->toBeTrue();

    $this->post(route('worker.institutes.leave', $member))->assertRedirect();
    expect($member->fresh()?->status)->toBe(CohortMemberStatus::Left)
        ->and($member->fresh()?->shares_progress)->toBeFalse()
        ->and($pool->workers()->count())->toBe(0);
    $this->get(route('worker.privacy.show'))->assertInertia(fn (Assert $page) => $page->has('institutes', 0));
    expect(session(CohortInviteController::SESSION_KEY))->toBeNull();
});
