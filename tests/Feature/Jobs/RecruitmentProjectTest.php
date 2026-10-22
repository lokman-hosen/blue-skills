<?php

use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\ApplicationStageEvent;
use App\Domain\Applications\States\Application\Accepted;
use App\Domain\Applications\States\Application\Applied;
use App\Domain\Applications\States\Application\Rejected;
use App\Domain\Applications\States\Application\Selected;
use App\Domain\Applications\States\Application\Shortlisted;
use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\Models\RecruitmentProject;
use App\Domain\Jobs\States\RecruitmentProject\Cancelled;
use App\Domain\Jobs\States\RecruitmentProject\Completed as ProjectCompleted;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Mobility\States\MobilityCase\Completed;
use App\Domain\Organization\Models\Organization;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, CountrySeeder::class]);
    $this->employer = Organization::factory()->employer()->verified()->create();
    $this->recruiter = orgMember($this->employer, OrganizationRole::Recruiter);
    $this->jobs = JobPosting::factory()->published()->count(2)->create(['organization_id' => $this->employer->id]);
});

function projectPayload(array $overrides = []): array
{
    return [
        'name' => '200 Electricians — Dubai', 'target_headcount' => 200, 'destination_country_code' => 'AE',
        'source_country_codes' => ['BD', 'NP'], 'job_ids' => [], 'member_ids' => [], ...$overrides,
    ];
}

it('creates a project grouping jobs with a target, countries and a team', function () {
    $this->actingAs($this->recruiter)->post(route('org.projects.store'), projectPayload([
        'job_ids' => $this->jobs->modelKeys(), 'member_ids' => [$this->recruiter->id],
    ]))->assertRedirect();

    $project = RecruitmentProject::query()->sole();
    expect($project->reference)->toBe('RP-0001')
        ->and($project->source_country_codes)->toBe(['BD', 'NP'])
        ->and($project->jobs()->count())->toBe(2)
        ->and($project->members()->pluck('users.id')->all())->toBe([$this->recruiter->id]);

    $this->actingAs($this->recruiter)->put(route('org.projects.update', $project), projectPayload(['job_ids' => [$this->jobs[0]->id]]))
        ->assertRedirect();
    expect($project->jobs()->count())->toBe(1)
        ->and($this->jobs[1]->fresh()?->recruitment_project_id)->toBeNull();
});

it('refuses jobs in another project, jobs of another organization and missing targets', function () {
    $other = RecruitmentProject::factory()->create(['organization_id' => $this->employer->id]);
    $this->jobs[0]->update(['recruitment_project_id' => $other->id]);
    $foreign = JobPosting::factory()->create();

    $this->actingAs($this->recruiter)->post(route('org.projects.store'), projectPayload(['job_ids' => [$this->jobs[0]->id]]))
        ->assertSessionHasErrors('job_ids');
    $this->actingAs($this->recruiter)->post(route('org.projects.store'), projectPayload(['job_ids' => [$foreign->id]]))
        ->assertSessionHasErrors('job_ids');
    $this->actingAs($this->recruiter)->post(route('org.projects.store'), projectPayload(['target_headcount' => 0, 'name' => '']))
        ->assertSessionHasErrors(['target_headcount', 'name']);

    expect(RecruitmentProject::query()->count())->toBe(1);
});

it('counts everyone who ever reached each stage, up to joining', function () {
    $project = RecruitmentProject::factory()->create(['organization_id' => $this->employer->id, 'target_headcount' => 10]);
    $this->jobs->each->update(['recruitment_project_id' => $project->id]);

    Application::factory()->create(['job_id' => $this->jobs[0]->id, 'status' => Applied::class]);
    Application::factory()->create(['job_id' => $this->jobs[0]->id, 'status' => Shortlisted::class]);
    $rejected = Application::factory()->create(['job_id' => $this->jobs[1]->id, 'status' => Rejected::class]);
    ApplicationStageEvent::query()->create(['application_id' => $rejected->id, 'from_status' => 'shortlisted', 'to_status' => 'interview']);
    Application::factory()->create(['job_id' => $this->jobs[1]->id, 'status' => Selected::class]);
    $joined = Application::factory()->create(['job_id' => $this->jobs[1]->id, 'status' => Accepted::class]);
    MobilityCase::factory()->create(['application_id' => $joined->id, 'status' => Completed::class]);

    $this->actingAs(orgMember($this->employer, OrganizationRole::Viewer))->get(route('org.projects.show', $project))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('org/projects/show')
            ->where('project.stages', [
                ['key' => 'applied', 'count' => 5], ['key' => 'qualified', 'count' => 4], ['key' => 'interview', 'count' => 3],
                ['key' => 'selected', 'count' => 2], ['key' => 'mobility', 'count' => 1], ['key' => 'joined', 'count' => 1],
            ])
            ->where('project.summary.joined', 1)
            ->has('applicants.data', 5)
            ->where('canManage', false));
});

it('shortlists and rejects applicants across the project\'s jobs in one go', function () {
    $first = Application::factory()->create(['job_id' => $this->jobs[0]->id]);
    $second = Application::factory()->create(['job_id' => $this->jobs[1]->id]);
    $accepted = Application::factory()->create(['job_id' => $this->jobs[1]->id, 'status' => Accepted::class]);

    $this->actingAs($this->recruiter)->post(route('org.applicants.move'), ['application_ids' => [$first->id, $second->id], 'stage' => 'shortlisted'])
        ->assertRedirect();
    expect($first->fresh()?->status)->toBeInstanceOf(Shortlisted::class)
        ->and($second->fresh()?->status)->toBeInstanceOf(Shortlisted::class);

    $this->actingAs($this->recruiter)->post(route('org.applicants.reject-many'), [
        'application_ids' => [$first->id, $accepted->id], 'reason' => 'requirements_not_met',
    ])->assertRedirect()->assertSessionHasNoErrors();
    expect($first->fresh()?->status)->toBeInstanceOf(Rejected::class)
        ->and($accepted->fresh()?->status)->toBeInstanceOf(Accepted::class);

    $foreign = Application::factory()->create();
    $this->actingAs($this->recruiter)->post(route('org.applicants.reject-many'), ['application_ids' => [$foreign->id], 'reason' => 'requirements_not_met'])
        ->assertNotFound();
    $this->actingAs(orgMember($this->employer, OrganizationRole::Viewer))
        ->post(route('org.applicants.reject-many'), ['application_ids' => [$second->id], 'reason' => 'requirements_not_met'])
        ->assertForbidden();
});

it('lets hiring roles complete, reopen and cancel projects; others only look', function () {
    $project = RecruitmentProject::factory()->create(['organization_id' => $this->employer->id]);
    $viewer = orgMember($this->employer, OrganizationRole::Viewer);

    $this->actingAs($viewer)->post(route('org.projects.store'), projectPayload())->assertForbidden();
    $this->actingAs($viewer)->post(route('org.projects.status', [$project, 'complete']))->assertForbidden();

    $this->actingAs($this->recruiter)->post(route('org.projects.status', [$project, 'complete']))->assertRedirect();
    expect($project->fresh()?->status)->toBeInstanceOf(ProjectCompleted::class);
    $this->actingAs($this->recruiter)->post(route('org.projects.status', [$project, 'cancel']))->assertSessionHasErrors('status');
    $this->actingAs($this->recruiter)->post(route('org.projects.status', [$project, 'reopen']));
    $this->actingAs($this->recruiter)->post(route('org.projects.status', [$project, 'cancel']));
    expect($project->fresh()?->status)->toBeInstanceOf(Cancelled::class);

    $agency = Organization::factory()->agency()->verified()->create();
    $this->actingAs(orgMember($agency, OrganizationRole::AgencyOwner))->get(route('org.projects.index'))->assertForbidden();

    $outsider = orgMember(Organization::factory()->employer()->verified()->create(), OrganizationRole::Owner);
    $this->actingAs($outsider)->get(route('org.projects.show', $project))->assertNotFound();
});
