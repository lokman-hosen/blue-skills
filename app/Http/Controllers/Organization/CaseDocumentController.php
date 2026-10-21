<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Mobility\Actions\DecideCaseDocument;
use App\Domain\Mobility\Actions\RequestCaseDocument;
use App\Domain\Mobility\Actions\UploadCaseDocument;
use App\Domain\Mobility\Enums\CaseDocumentReturnReason;
use App\Domain\Mobility\Enums\DocumentProvider;
use App\Domain\Mobility\Models\CaseDocument;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Verification\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\DecideCaseDocumentRequest;
use App\Http\Requests\Organization\StoreCaseDocumentRequest;
use App\Http\Requests\Organization\UploadCaseDocumentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

/**
 * The managing organization's side of the case checklist: ask for documents, add the ones it
 * provides, and check what the worker shared (D-079).
 */
class CaseDocumentController extends Controller
{
    use ResolvesCurrentOrganization;

    public function store(StoreCaseDocumentRequest $request, MobilityCase $mobilityCase, RequestCaseDocument $requestDocument): RedirectResponse
    {
        $this->managedHere($request, $mobilityCase);
        Gate::authorize('manage', $mobilityCase);

        $requestDocument->handle(
            $mobilityCase,
            DocumentType::from($request->string('type')->toString()),
            $request->string('title')->trim()->toString() ?: null,
            DocumentProvider::from($request->string('provided_by')->toString()),
            $request->input('due_on') ?: null,
            $request->string('note')->trim()->toString() ?: null,
            $request->user(),
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => __('mobility.document.done.requested')]);

        return to_route('org.cases.show', $mobilityCase);
    }

    public function upload(UploadCaseDocumentRequest $request, MobilityCase $mobilityCase, CaseDocument $document, UploadCaseDocument $upload): RedirectResponse
    {
        $this->managedHere($request, $mobilityCase);
        Gate::authorize('manage', $mobilityCase);

        $file = $request->file('document');
        abort_unless($file instanceof UploadedFile, 422);

        $upload->handle($document, $file, $request->boolean('employer_can_view', true), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('mobility.document.done.uploaded')]);

        return to_route('org.cases.show', $mobilityCase);
    }

    public function decide(DecideCaseDocumentRequest $request, MobilityCase $mobilityCase, CaseDocument $document, string $decision, DecideCaseDocument $decide): RedirectResponse
    {
        $this->managedHere($request, $mobilityCase);
        Gate::authorize('manage', $mobilityCase);

        $decide->handle($document, $decision, $request->enum('reason', CaseDocumentReturnReason::class), $request->string('note')->trim()->toString() ?: null, $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __("mobility.document.done.{$decision}")]);

        return to_route('org.cases.show', $mobilityCase);
    }

    /**
     * Only the organization running the case changes its checklist.
     */
    private function managedHere(Request $request, MobilityCase $mobilityCase): void
    {
        abort_unless($this->organization($request)->is($mobilityCase->managingOrganization), 404);
    }
}
