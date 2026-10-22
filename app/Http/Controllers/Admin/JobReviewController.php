<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Jobs\Actions\DecideJobReview;
use App\Domain\Jobs\Data\JobReviewItemData;
use App\Domain\Jobs\Data\JobTruthCardData;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\Support\JobCompliance;
use App\Domain\Verification\Data\ReviewDecisionData;
use App\Domain\Verification\Enums\ReviewReason;
use App\Domain\Verification\Enums\VerificationType;
use App\Domain\Verification\Models\VerificationRequest;
use App\Domain\Verification\States\VerificationRequest\InReview;
use App\Domain\Verification\States\VerificationRequest\Open;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewDecisionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Staff review of submitted jobs and their Truth Cards (spec §5, §10).
 */
class JobReviewController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('moderate', JobPosting::class);

        $reviews = VerificationRequest::query()
            ->with(['subject' => fn ($query) => $query->with(['employer', 'destinationCountry', 'costs', 'mandate.agency'])])
            ->where('type', VerificationType::Job)
            ->whereIn('status', ['open', 'in_review'])
            ->oldest()
            ->limit(100)
            ->get();

        return Inertia::render('admin/jobs/index', [
            'reviews' => $reviews->map(fn (VerificationRequest $review) => JobReviewItemData::fromRequest($review))->values()->all(),
        ]);
    }

    public function show(VerificationRequest $review): Response
    {
        Gate::authorize('moderate', JobPosting::class);
        abort_unless($review->type === VerificationType::Job && $review->subject instanceof JobPosting, 404);

        $job = $review->subject;

        return Inertia::render('admin/jobs/show', [
            'review' => JobReviewItemData::fromRequest($review),
            'job' => JobTruthCardData::fromJob($job),
            'problems' => JobCompliance::problems($job),
            'history' => $job->verificationRequests()->with('decider')->whereNotNull('decision')->latest('decided_at')->get()
                ->map(fn (VerificationRequest $past) => ReviewDecisionData::fromRequest($past, withReviewer: true))
                ->filter()
                ->values()
                ->all(),
            'decidable' => $review->status->equals(Open::class, InReview::class),
            'reasons' => ReviewReason::forJobs(),
        ]);
    }

    public function decide(ReviewDecisionRequest $request, VerificationRequest $review, DecideJobReview $decide): RedirectResponse
    {
        Gate::authorize('moderate', JobPosting::class);
        abort_unless($review->type === VerificationType::Job, 404);

        $decide->handle($review, $request->user(), $request->decision(), $request->reason(), $request->note());
        Inertia::flash('toast', ['type' => 'success', 'message' => __("admin.jobs.done.{$request->decision()->value}")]);

        return to_route('admin.jobs.index');
    }
}
