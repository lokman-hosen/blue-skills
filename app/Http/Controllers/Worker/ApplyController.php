<?php

namespace App\Http\Controllers\Worker;

use App\Domain\Applications\Actions\CreateApplication;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Support\ShareableItems;
use App\Domain\Jobs\Data\JobListingData;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\Support\JobMatch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\ApplyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Share with employer?" and confirming an application (spec §6, deck slide 12).
 */
class ApplyController extends Controller
{
    use ResolvesWorkerProfile;

    public function create(Request $request, JobPosting $job): Response|RedirectResponse
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('view', $job);
        Gate::authorize('create', [Application::class, $job]);

        $existing = $profile->applications()->where('job_id', $job->id)->first();

        if ($existing !== null) {
            return to_route('worker.applications.show', $existing);
        }

        return Inertia::render('worker/jobs/apply', [
            'job' => JobListingData::fromJob($job, JobMatch::explain($profile, $job)),
            'items' => array_values(ShareableItems::for($profile)),
            'acceptsApplications' => $job->acceptsApplications(),
            'hasTrade' => $profile->occupation_id !== null,
        ]);
    }

    public function store(ApplyRequest $request, JobPosting $job, CreateApplication $create): RedirectResponse
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('view', $job);
        Gate::authorize('create', [Application::class, $job]);

        $application = $create->handle($profile, $job, $request->items());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('applications.apply.done')]);

        return to_route('worker.applications.show', $application);
    }
}
