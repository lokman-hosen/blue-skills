<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Jobs\Actions\DecideMandateReview;
use App\Domain\Jobs\Data\MandateData;
use App\Domain\Jobs\Enums\MandateStatus;
use App\Domain\Jobs\Models\JobMandate;
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
 * Staff review of agency mandates the employer has confirmed (D-074, product rule 10).
 */
class MandateReviewController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('review', JobMandate::class);

        $reviews = VerificationRequest::query()
            ->where('type', VerificationType::JobMandate)
            ->whereIn('status', ['open', 'in_review'])
            ->whereHasMorph('subject', [JobMandate::class], fn ($query) => $query->where('status', MandateStatus::Pending))
            ->with('subject')
            ->oldest()
            ->limit(100)
            ->get();

        return Inertia::render('admin/mandates/index', [
            'reviews' => $reviews->map(fn (VerificationRequest $review) => [
                'id' => $review->id,
                'submittedAt' => $review->created_at?->toIso8601String(),
                'mandate' => $review->subject instanceof JobMandate ? MandateData::fromMandate($review->subject) : null,
            ])->values()->all(),
        ]);
    }

    public function show(VerificationRequest $review): Response
    {
        Gate::authorize('review', JobMandate::class);
        $mandate = $review->subject;
        abort_unless($review->type === VerificationType::JobMandate && $mandate instanceof JobMandate, 404);

        return Inertia::render('admin/mandates/show', [
            'reviewId' => $review->id,
            'mandate' => MandateData::fromMandate($mandate, withEvidence: true),
            'licence' => [
                'number' => $mandate->agency->licence_number,
                'expiresOn' => $mandate->agency->licence_expires_on?->toDateString(),
                'countries' => $mandate->agency->licence_countries ?? [],
                'valid' => $mandate->agency->hasValidLicence($mandate->destination_country_code),
            ],
            'history' => $mandate->verificationRequests()->with('decider')->whereNotNull('decision')->latest('decided_at')->get()
                ->map(fn (VerificationRequest $past) => ReviewDecisionData::fromRequest($past, withReviewer: true))
                ->filter()
                ->values()
                ->all(),
            'decidable' => $review->status->equals(Open::class, InReview::class) && $mandate->status === MandateStatus::Pending,
            'reasons' => ReviewReason::forMandates(),
        ]);
    }

    public function decide(ReviewDecisionRequest $request, VerificationRequest $review, DecideMandateReview $decide): RedirectResponse
    {
        Gate::authorize('review', JobMandate::class);
        abort_unless($review->type === VerificationType::JobMandate, 404);

        $decide->handle($review, $request->user(), $request->decision(), $request->reason(), $request->note());
        Inertia::flash('toast', ['type' => 'success', 'message' => __("admin.mandates.done.{$request->decision()->value}")]);

        return to_route('admin.mandates.index');
    }
}
