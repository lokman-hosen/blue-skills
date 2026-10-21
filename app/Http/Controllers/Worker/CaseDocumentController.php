<?php

namespace App\Http\Controllers\Worker;

use App\Domain\Mobility\Actions\AnswerCaseContract;
use App\Domain\Mobility\Actions\RevokeCaseDocument;
use App\Domain\Mobility\Actions\SetCaseDocumentVisibility;
use App\Domain\Mobility\Actions\ShareCaseDocument;
use App\Domain\Mobility\Models\CaseDocument;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Verification\Models\Document;
use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\AnswerCaseContractRequest;
use App\Http\Requests\Worker\ShareCaseDocumentRequest;
use App\Http\Requests\Worker\UpdateCaseDocumentVisibilityRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * The worker's side of the case checklist: share a file per item, choose whether the employer of
 * an agency job sees it, stop sharing, and confirm the contract (product rule 6, D-079).
 */
class CaseDocumentController extends Controller
{
    use ResolvesWorkerProfile;

    public function share(ShareCaseDocumentRequest $request, MobilityCase $mobilityCase, CaseDocument $document, ShareCaseDocument $share): RedirectResponse
    {
        Gate::authorize('respond', $mobilityCase);
        $profile = $this->workerProfile($request);

        $file = $request->file('document');
        $source = $file instanceof UploadedFile
            ? $file
            : Document::query()->where('owner_user_id', $profile->user_id)->findOrFail($request->string('document_id')->toString());

        $share->handle($document, $profile, $source, $request->boolean('employer_can_view'));
        Inertia::flash('toast', ['type' => 'success', 'message' => __('mobility.document.done.shared')]);

        return to_route('worker.cases.show', $mobilityCase);
    }

    public function visibility(UpdateCaseDocumentVisibilityRequest $request, MobilityCase $mobilityCase, CaseDocument $document, SetCaseDocumentVisibility $set): RedirectResponse
    {
        Gate::authorize('respond', $mobilityCase);
        abort_unless($document->fromWorker(), 403);

        $set->handle($document, $request->boolean('employer_can_view'), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('mobility.document.done.visibility')]);

        return to_route('worker.cases.show', $mobilityCase);
    }

    public function revoke(Request $request, MobilityCase $mobilityCase, CaseDocument $document, RevokeCaseDocument $revoke): RedirectResponse
    {
        Gate::authorize('respond', $mobilityCase);

        $revoke->handle($document, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('mobility.document.done.revoked')]);

        return to_route('worker.cases.show', $mobilityCase);
    }

    public function contract(AnswerCaseContractRequest $request, MobilityCase $mobilityCase, CaseDocument $document, string $answer, AnswerCaseContract $contract): RedirectResponse
    {
        Gate::authorize('respond', $mobilityCase);

        $contract->handle($document, $answer, $request->string('note')->trim()->toString() ?: null, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __("mobility.document.done.contract_{$answer}")]);

        return to_route('worker.cases.show', $mobilityCase);
    }
}
