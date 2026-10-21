<?php

namespace App\Http\Controllers\Public;

use App\Domain\Config\Data\CountryOptionData;
use App\Domain\Config\Models\Country;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Data\JobListingData;
use App\Domain\Jobs\Data\JobTruthCardData;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\Support\JobMatch;
use App\Domain\Jobs\Support\JobSearch;
use App\Domain\Taxonomy\Data\TaxonomyOptionData;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Trust\Actions\ReportJob;
use App\Domain\Trust\Enums\ReportCategory;
use App\Domain\Worker\Models\WorkerProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\ReportJobRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Find Jobs and the Job Truth Card (spec §5, §8, §14). Guests see the public pages; workers see
 * the same jobs inside their workspace with "X of Y requirements met".
 */
class JobController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => mb_substr($request->string('q')->trim()->toString(), 0, 100) ?: null,
            'country' => $request->string('country')->toString() ?: null,
            'occupation' => $request->string('occupation')->toString() ?: null,
            'no_worker_costs' => $request->boolean('no_worker_costs'),
            'accommodation' => $request->boolean('accommodation'),
            'salary_disclosed' => $request->boolean('salary_disclosed'),
            'sort' => in_array($request->string('sort')->toString(), JobSearch::SORTS, true) ? $request->string('sort')->toString() : 'newest',
        ];

        $profile = $this->workerProfile($request->user());
        $jobs = JobSearch::run($filters);

        return Inertia::render($profile !== null ? 'worker/jobs/index' : 'public/jobs/index', [
            'jobs' => $jobs->through(fn (JobPosting $job) => JobListingData::fromJob($job, $profile !== null ? JobMatch::explain($profile, $job) : null)),
            'filters' => $filters,
            'countries' => Country::query()->where('enabled_as_destination', true)->orderBy('name')->get()
                ->map(fn (Country $country) => CountryOptionData::fromCountry($country))->values()->all(),
            'occupations' => Occupation::query()->orderBy('slug')->get()
                ->map(fn (Occupation $occupation) => TaxonomyOptionData::fromModel($occupation))->values()->all(),
        ]);
    }

    public function show(Request $request, JobPosting $job): Response
    {
        Gate::authorize('view', $job);

        $profile = $this->workerProfile($request->user());

        return Inertia::render($profile !== null ? 'worker/jobs/show' : 'public/jobs/show', [
            'job' => JobTruthCardData::fromJob($job),
            'match' => $profile !== null ? JobMatch::explain($profile, $job) : null,
            'reportCategories' => [
                ReportCategory::FakeJob, ReportCategory::UnauthorizedPayment, ReportCategory::MisleadingSalary,
                ReportCategory::Impersonation, ReportCategory::Other,
            ],
            'canReport' => $request->user() !== null,
            'application' => $profile?->applications()->where('job_id', $job->id)->first()?->only(['id']),
            'acceptsApplications' => $job->acceptsApplications(),
        ]);
    }

    public function report(ReportJobRequest $request, JobPosting $job, ReportJob $report): RedirectResponse
    {
        Gate::authorize('view', $job);

        $report->handle(
            $job,
            $request->user(),
            ReportCategory::from($request->string('category')->toString()),
            $request->filled('description') ? $request->string('description')->trim()->toString() : null,
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => __('jobs.report.thanks')]);

        return back();
    }

    private function workerProfile(?User $user): ?WorkerProfile
    {
        return $user?->isWorker() ? $user->workerProfile : null;
    }
}
