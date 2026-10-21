<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Applications\Data\CandidateListItemData;
use App\Domain\Applications\Models\Application;
use App\Domain\Jobs\Models\JobPosting;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Applicants across all of the organization's jobs (agency "Candidates", D-080), from their
 * application snapshots. Each row opens the job's applicant page.
 */
class CandidateController extends Controller
{
    use ResolvesCurrentOrganization;

    public function index(Request $request): Response
    {
        $organization = $this->organization($request);
        Gate::authorize('viewAny', [Application::class, $organization]);

        $jobs = $organization->postedJobs()->orderBy('title')->get(['id', 'public_code', 'title']);
        $job = $jobs->firstWhere('public_code', $request->string('job')->toString());
        $stage = in_array($request->string('stage')->toString(), ApplicantController::FILTERS, true) ? $request->string('stage')->toString() : null;
        $search = $request->string('q')->trim()->limit(80, '')->toString();

        $candidates = Application::query()
            ->whereIn('job_id', $jobs->modelKeys())
            ->when($job !== null, fn ($query) => $query->where('job_id', $job?->id))
            ->when($stage !== null, fn ($query) => $query->where('status', $stage))
            ->when($search !== '', fn ($query) => $query->whereHas('workerProfile', fn ($profiles) => $profiles
                ->where('reference', 'ilike', "%{$search}%")
                ->orWhereHas('user', fn ($users) => $users->where('name', 'ilike', "%{$search}%"))))
            ->with(['job', 'snapshot', 'consents', 'assignee', 'interviews', 'workerProfile.user', 'mobilityCase'])
            ->latest('applied_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('org/candidates/index', [
            'candidates' => $candidates->through(fn (Application $application) => CandidateListItemData::fromApplication($application)),
            'jobs' => $jobs->map(fn (JobPosting $posting) => ['code' => $posting->public_code, 'title' => $posting->title])->values()->all(),
            'stages' => ApplicantController::FILTERS,
            'filters' => ['job' => $job?->public_code, 'stage' => $stage, 'q' => $search],
        ]);
    }
}
