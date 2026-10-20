<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Jobs\Actions\ChangeJobAvailability;
use App\Domain\Jobs\Actions\SaveJobStep;
use App\Domain\Jobs\Actions\SubmitJobForReview;
use App\Domain\Jobs\Data\JobFormData;
use App\Domain\Jobs\Data\JobFormOptionsData;
use App\Domain\Jobs\Data\JobListItemData;
use App\Domain\Jobs\Data\JobTruthCardData;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\Support\JobCompliance;
use App\Domain\Jobs\Support\JobWizard;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Support\CurrentOrganization;
use App\Domain\Verification\Data\ReviewDecisionData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\SaveJobStepRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The current organization's jobs: list, Post a Job wizard, submit, pause, resume and close
 * (spec §5, prototype "Post job").
 */
class JobController extends Controller
{
    public function __construct(private readonly CurrentOrganization $currentOrganization) {}

    public function index(Request $request): Response
    {
        $organization = $this->organization($request);
        Gate::authorize('viewAny', [JobPosting::class, $organization]);

        $jobs = $organization->postedJobs()
            ->with(['occupation', 'destinationCountry', 'verificationRequests'])
            ->latest('updated_at')
            ->get();

        return Inertia::render('org/jobs/index', [
            'jobs' => $jobs->map(fn (JobPosting $job) => JobListItemData::fromJob($job))->values()->all(),
            'canCreate' => Gate::allows('create', [JobPosting::class, $organization]),
        ]);
    }

    public function create(Request $request): Response
    {
        $organization = $this->organization($request);
        Gate::authorize('create', [JobPosting::class, $organization]);

        return $this->wizard($organization, null, 'requirement');
    }

    public function store(SaveJobStepRequest $request, SaveJobStep $save): RedirectResponse
    {
        $organization = $this->organization($request);
        Gate::authorize('create', [JobPosting::class, $organization]);

        $job = $save->handle($organization, null, 'requirement', $request->validated(), $request->user());

        return to_route('org.jobs.edit', [$job, 'skills']);
    }

    public function edit(Request $request, JobPosting $job, string $step): Response
    {
        $organization = $this->ownJob($request, $job);
        Gate::authorize('update', $job);

        return $this->wizard($organization, $job, $step);
    }

    public function update(SaveJobStepRequest $request, JobPosting $job, string $step, SaveJobStep $save): RedirectResponse
    {
        $organization = $this->ownJob($request, $job);
        Gate::authorize('update', $job);
        $wasLive = $job->isLive();

        $job = $save->handle($organization, $job, $step, $request->validated(), $request->user());

        if ($wasLive && ! $job->isLive()) {
            Inertia::flash('toast', ['type' => 'info', 'message' => __('jobs.material_change_review')]);

            return to_route('org.jobs.show', $job);
        }

        $next = JobWizard::STEPS[array_search($step, JobWizard::STEPS, true) + 1] ?? 'preview';

        return to_route('org.jobs.edit', [$job, $next]);
    }

    public function show(Request $request, JobPosting $job): Response
    {
        $this->ownJob($request, $job);
        Gate::authorize('view', $job);

        $review = $job->latestReview();

        return Inertia::render('org/jobs/show', [
            'job' => JobTruthCardData::fromJob($job),
            'lastDecision' => $review !== null ? ReviewDecisionData::fromRequest($review) : null,
            'abilities' => [
                'update' => Gate::allows('update', $job),
                'submit' => Gate::allows('submit', $job),
                'manage' => Gate::allows('manage', $job),
            ],
        ]);
    }

    public function submit(Request $request, JobPosting $job, SubmitJobForReview $submit): RedirectResponse
    {
        $this->ownJob($request, $job);
        Gate::authorize('submit', $job);

        $submit->handle($job, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('jobs.submitted')]);

        return to_route('org.jobs.show', $job);
    }

    public function availability(Request $request, JobPosting $job, string $action, ChangeJobAvailability $change): RedirectResponse
    {
        $this->ownJob($request, $job);
        Gate::authorize('manage', $job);

        $change->handle($job, $action, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __("jobs.availability.done.{$action}")]);

        return to_route($action === 'archive' ? 'org.jobs.index' : 'org.jobs.show', $action === 'archive' ? [] : [$job]);
    }

    private function wizard(Organization $organization, ?JobPosting $job, string $step): Response
    {
        return Inertia::render('org/jobs/wizard', [
            'step' => $step,
            'steps' => [...JobWizard::STEPS, 'preview'],
            'code' => $job?->public_code,
            'form' => $job !== null ? JobFormData::fromJob($job) : null,
            'preview' => $job !== null ? JobTruthCardData::fromJob($job) : null,
            'problems' => $job !== null ? JobCompliance::problems($job) : [],
            'live' => $job?->isLive() ?? false,
            'organizationVerified' => $organization->isVerified(),
            'options' => JobFormOptionsData::load(),
        ]);
    }

    private function organization(Request $request): Organization
    {
        $organization = $this->currentOrganization->resolve($request->user(), $request->session());
        abort_if($organization === null, 403);

        return $organization;
    }

    /**
     * Jobs are reached through the current organization only, never another one's code.
     */
    private function ownJob(Request $request, JobPosting $job): Organization
    {
        $organization = $this->organization($request);
        abort_unless($job->organization_id === $organization->id, 404);

        return $organization;
    }
}
