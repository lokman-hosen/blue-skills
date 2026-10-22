<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Applications\Data\ApplicantListItemData;
use App\Domain\Applications\Enums\RejectionReason;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Support\ApplicationStages;
use App\Domain\Config\Models\Country;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Actions\ChangeRecruitmentProjectStatus;
use App\Domain\Jobs\Actions\SaveRecruitmentProject;
use App\Domain\Jobs\Data\RecruitmentProjectData;
use App\Domain\Jobs\Data\RecruitmentProjectListItemData;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\Models\RecruitmentProject;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Support\OrganizationPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\SaveRecruitmentProjectRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Bulk-hiring recruitment projects (spec §6, deck slide 18): jobs grouped under a target
 * headcount, stage counts, the team, and the project's applicants for bulk shortlisting.
 */
class RecruitmentProjectController extends Controller
{
    use ResolvesCurrentOrganization;

    public function index(Request $request): Response
    {
        $organization = $this->organization($request);
        Gate::authorize('viewAny', [RecruitmentProject::class, $organization]);

        $projects = $organization->recruitmentProjects()
            ->orderByRaw("case status when 'active' then 0 else 1 end")
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('org/projects/index', [
            'projects' => $projects->through(fn (RecruitmentProject $project) => RecruitmentProjectListItemData::fromProject($project)),
            'canManage' => Gate::allows('create', [RecruitmentProject::class, $organization]),
            ...$this->formOptions($organization, null),
        ]);
    }

    public function store(SaveRecruitmentProjectRequest $request, SaveRecruitmentProject $save): RedirectResponse
    {
        $organization = $this->organization($request);
        Gate::authorize('create', [RecruitmentProject::class, $organization]);

        $project = $save->handle($organization, null, $request->project(), $request->ids('job_ids'), $request->ids('member_ids'), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('projects.done.created', ['reference' => $project->reference])]);

        return to_route('org.projects.show', $project);
    }

    public function show(Request $request, RecruitmentProject $project): Response
    {
        $organization = $this->ownProject($request, $project);
        Gate::authorize('view', $project);

        $stage = in_array($request->string('stage')->toString(), ApplicantController::FILTERS, true) ? $request->string('stage')->toString() : null;
        $applicants = RecruitmentProjectData::applicants($project)
            ->with(['snapshot', 'consents', 'assignee', 'interviews', 'workerProfile.user', 'job'])
            ->when($stage !== null, fn ($query) => $query->where('status', $stage))
            ->oldest('applied_at')
            ->paginate(50)
            ->withQueryString();

        return Inertia::render('org/projects/show', [
            'project' => RecruitmentProjectData::fromProject($project),
            'applicants' => $applicants->through(fn (Application $application) => [
                ...ApplicantListItemData::fromApplication($application)->toArray(),
                'jobTitle' => $application->job->title,
            ]),
            'stage' => $stage,
            'filters' => ApplicantController::FILTERS,
            'movable' => ApplicationStages::MOVABLE,
            'rejectionReasons' => RejectionReason::cases(),
            'canManage' => Gate::allows('update', $project),
            'canMoveApplicants' => OrganizationPermissions::allows($request->user(), $organization, Permission::ApplicationsManage),
            ...$this->formOptions($organization, $project),
        ]);
    }

    public function update(SaveRecruitmentProjectRequest $request, RecruitmentProject $project, SaveRecruitmentProject $save): RedirectResponse
    {
        $organization = $this->ownProject($request, $project);
        Gate::authorize('update', $project);

        $save->handle($organization, $project, $request->project(), $request->ids('job_ids'), $request->ids('member_ids'), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('projects.done.updated')]);

        return to_route('org.projects.show', $project);
    }

    public function status(Request $request, RecruitmentProject $project, string $action, ChangeRecruitmentProjectStatus $change): RedirectResponse
    {
        $this->ownProject($request, $project);
        Gate::authorize('update', $project);
        abort_unless(in_array($action, ChangeRecruitmentProjectStatus::ACTIONS, true), 404);

        $change->handle($project, $action);
        Inertia::flash('toast', ['type' => 'success', 'message' => __("projects.done.{$action}")]);

        return to_route('org.projects.show', $project);
    }

    private function ownProject(Request $request, RecruitmentProject $project): Organization
    {
        $organization = $this->organization($request);
        abort_unless($project->organization_id === $organization->id, 404);

        return $organization;
    }

    /**
     * Jobs that can join the project (not in another one), the team, and countries.
     *
     * @return array{jobOptions: list<array{id: string, label: string, projectId: ?string}>, memberOptions: list<array{id: string, name: string}>, countries: list<array{code: string, name: string}>, selected: array{jobIds: list<string>, memberIds: list<string>}}
     */
    private function formOptions(Organization $organization, ?RecruitmentProject $project): array
    {
        return [
            'jobOptions' => JobPosting::query()->where('organization_id', $organization->id)
                ->where(fn ($query) => $query->whereNull('recruitment_project_id')->orWhere('recruitment_project_id', $project?->id))
                ->orderBy('title')->get(['id', 'title', 'public_code', 'recruitment_project_id'])
                ->map(fn (JobPosting $job) => ['id' => $job->id, 'label' => "{$job->title} · {$job->public_code}", 'projectId' => $job->recruitment_project_id])
                ->values()->all(),
            'memberOptions' => $organization->users()->orderBy('name')->get()
                ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])->values()->all(),
            'countries' => Country::query()->orderBy('name')->get(['code', 'name'])
                ->map(fn (Country $country) => ['code' => $country->code, 'name' => (string) $country->name])->values()->all(),
            'selected' => [
                'jobIds' => $project !== null ? array_values($project->jobs()->pluck('id')->all()) : [],
                'memberIds' => $project !== null ? array_values($project->members()->pluck('users.id')->all()) : [],
            ],
        ];
    }
}
