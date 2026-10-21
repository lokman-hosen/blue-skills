<?php

namespace App\Http\Controllers\Worker;

use App\Domain\Verification\Enums\DocumentType;
use App\Domain\Verification\Models\Document;
use App\Domain\Worker\Actions\AddIdentityDocument;
use App\Domain\Worker\Actions\RemoveIdentityDocument;
use App\Domain\Worker\Data\IdentityDocumentData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\StoreIdentityDocumentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Passport and national ID scans (D-047). Private files, shared only per application with
 * consent (product rule 6).
 */
class DocumentController extends Controller
{
    use ResolvesWorkerProfile;

    public function index(Request $request): Response
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('view', $profile);

        $documents = Document::query()
            ->where('owner_user_id', $profile->user_id)
            ->whereIn('type', AddIdentityDocument::TYPES)
            ->with('evidenceItems')
            ->latest()
            ->get();

        return Inertia::render('worker/documents', [
            'documents' => $documents->map(fn (Document $document) => IdentityDocumentData::fromDocument($document))->values()->all(),
            'types' => AddIdentityDocument::TYPES,
            'maxSizeMb' => StoreIdentityDocumentRequest::MAX_KB / 1024,
        ]);
    }

    public function store(StoreIdentityDocumentRequest $request, AddIdentityDocument $add): RedirectResponse
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);

        $file = $request->file('document');
        abort_unless($file instanceof UploadedFile, 422);

        $add->handle(
            $profile,
            DocumentType::from($request->string('type')->toString()),
            $request->filled('expires_on') ? $request->string('expires_on')->toString() : null,
            $file,
        );
        Inertia::flash('toast', ['type' => 'success', 'message' => __('documents.uploaded')]);

        return to_route('worker.documents.index');
    }

    public function destroy(Request $request, Document $document, RemoveIdentityDocument $remove): RedirectResponse
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);
        abort_unless($document->owner_user_id === $profile->user_id && in_array($document->type, AddIdentityDocument::TYPES, true), 404);

        $remove->handle($profile, $document);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('documents.removed')]);

        return to_route('worker.documents.index');
    }
}
