<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Verification\Actions\DecideEvidenceReview;
use App\Domain\Verification\Actions\StartEvidenceReview;
use App\Domain\Verification\Data\EvidenceReviewData;
use App\Domain\Verification\Enums\ReviewReason;
use App\Domain\Verification\Models\Document;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Verification\States\Evidence\UploadedUnreviewed;
use App\Domain\Verification\States\Evidence\VerificationPending;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewDecisionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Staff review of evidence workers upload: passport and ID scans, certificates and work
 * references (spec §10, product rule 9, D-090). Institute-issued records aren't reviewed here.
 */
class EvidenceReviewController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('review', EvidenceItem::class);

        $items = EvidenceItem::query()
            ->whereState('status', [UploadedUnreviewed::class, VerificationPending::class])
            ->where('documentable_type', (new Document)->getMorphClass())
            ->with(['documentable', 'workerProfile.user', 'verificationRequests.assignee', 'verificationRequests.decider'])
            ->oldest()
            ->paginate(50);

        return Inertia::render('admin/evidence/index', [
            'items' => $items->through(fn (EvidenceItem $item) => EvidenceReviewData::fromItem($item)),
        ]);
    }

    public function show(EvidenceItem $evidence): Response
    {
        Gate::authorize('review', EvidenceItem::class);
        abort_unless($evidence->documentable instanceof Document, 404);
        $evidence->load(['workerProfile.user', 'verificationRequests.assignee', 'verificationRequests.decider']);

        return Inertia::render('admin/evidence/show', [
            'item' => EvidenceReviewData::fromItem($evidence, withFile: Gate::allows('viewFile', $evidence)),
            'reasons' => ReviewReason::forEvidence(),
            'canDecide' => Gate::allows('viewFile', $evidence),
        ]);
    }

    public function start(Request $request, EvidenceItem $evidence, StartEvidenceReview $start): RedirectResponse
    {
        Gate::authorize('review', EvidenceItem::class);

        $start->handle($evidence, $request->user());

        return to_route('admin.evidence.show', $evidence);
    }

    public function decide(ReviewDecisionRequest $request, EvidenceItem $evidence, DecideEvidenceReview $decide): RedirectResponse
    {
        Gate::authorize('review', EvidenceItem::class);

        $decide->handle($evidence, $request->user(), $request->decision(), $request->enum('reason', ReviewReason::class), $request->string('note')->trim()->toString() ?: null);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('admin.evidence.decided')]);

        return to_route('admin.evidence.index');
    }
}
