<?php

use App\Domain\Applications\Enums\InvitationStatus;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\ApplicationStageEvent;
use App\Domain\Applications\Models\JobInvitation;
use App\Domain\Applications\States\Application\Offer;
use App\Domain\Applications\States\Application\Shortlisted;
use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\Models\RecruitmentProject;
use App\Domain\Organization\Models\Organization;
use App\Domain\Worker\Models\WorkerProfile;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, CountrySeeder::class]);
    $this->employer = Organization::factory()->employer()->verified()->create();
    $this->job = JobPosting::factory()->published()->create(['organization_id' => $this->employer->id]);
});

it('shows stage counts, median days, where applicants come from and active projects', function () {
    $nepali = WorkerProfile::factory()->create(['current_country_code' => 'NP']);
    $shortlisted = Application::factory()->create([
        'job_id' => $this->job->id, 'status' => Shortlisted::class, 'applied_at' => now()->subDays(10),
    ]);
    $offered = Application::factory()->create([
        'job_id' => $this->job->id, 'worker_profile_id' => $nepali->id, 'status' => Offer::class, 'applied_at' => now()->subDays(10),
    ]);
    $event = function (Application $application, string $status, int $daysAfter): void {
        $this->travelTo($application->applied_at->copy()->addDays($daysAfter));
        ApplicationStageEvent::query()->create(['application_id' => $application->id, 'to_status' => $status]);
        $this->travelBack();
    };
    $event($shortlisted, 'shortlisted', 2);
    $event($offered, 'shortlisted', 4);
    $event($offered, 'offer', 6);
    JobInvitation::factory()->create(['job_id' => $this->job->id, 'worker_profile_id' => $nepali->id, 'status' => InvitationStatus::Applied]);
    Application::factory()->create(['job_id' => $this->job->id, 'applied_at' => now()->subYear()]);
    Application::factory()->create(['applied_at' => now()->subDay()]);
    RecruitmentProject::factory()->create(['organization_id' => $this->employer->id, 'name' => 'Dubai Metro Electricians']);

    $this->actingAs(orgMember($this->employer, OrganizationRole::Viewer))->get(route('org.analytics.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('org/analytics')
            ->where('analytics.liveJobs', 1)
            ->where('analytics.stages.0', ['key' => 'applied', 'count' => 2])
            ->where('analytics.stages.1', ['key' => 'qualified', 'count' => 2])
            ->where('analytics.stages.3', ['key' => 'selected', 'count' => 1])
            ->where('analytics.medianDaysToShortlist', 3)
            ->where('analytics.medianDaysToOffer', 6)
            ->where('analytics.sources', ['invited' => 1, 'direct' => 1, 'withdrawn' => 0])
            ->where('analytics.countries', fn ($countries) => collect($countries)->contains(fn ($row) => $row['code'] === 'NP' && $row['count'] === 1))
            ->where('analytics.jobs.0.counts.applied', 2)
            ->where('analytics.projects.0.name', 'Dubai Metro Electricians'));

    $this->actingAs(orgMember($this->employer, OrganizationRole::Owner))
        ->get(route('org.analytics.index', ['from' => now()->subYears(2)->toDateString(), 'to' => now()->toDateString()]))
        ->assertInertia(fn (Assert $page) => $page->where('analytics.stages.0.count', 3));
});

it('opens analytics to employers and agencies, not institutes', function () {
    $agency = Organization::factory()->agency()->verified()->create();
    $this->actingAs(orgMember($agency, OrganizationRole::CaseOfficer))->get(route('org.analytics.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('analytics.projects', []));

    $institute = Organization::factory()->institute()->verified()->create();
    $this->actingAs(orgMember($institute, OrganizationRole::InstituteOwner))->get(route('org.analytics.index'))->assertForbidden();
});
