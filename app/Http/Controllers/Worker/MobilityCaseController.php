<?php

namespace App\Http\Controllers\Worker;

use App\Domain\Mobility\Data\CaseDocumentData;
use App\Domain\Mobility\Data\MobilityCaseData;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Mobility\Support\CaseChecklist;
use App\Domain\Verification\Models\Document;
use App\Domain\Worker\Data\IdentityDocumentData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\StoreIdentityDocumentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The worker's journey after accepting an offer (prototype w-case, spec §6).
 */
class MobilityCaseController extends Controller
{
    public function show(Request $request, MobilityCase $mobilityCase): Response
    {
        Gate::authorize('view', $mobilityCase);

        // Passport and ID scans already on the Documents page can be shared without uploading again.
        $scans = Document::query()
            ->where('owner_user_id', $request->user()?->id)
            ->whereIn('type', CaseChecklist::IDENTITY)
            ->with('evidenceItems')
            ->latest()
            ->get();

        return Inertia::render('worker/cases/show', [
            'mobilityCase' => MobilityCaseData::fromCase($mobilityCase, forWorker: true),
            'documents' => CaseDocumentData::forViewer($mobilityCase, CaseDocumentData::VIEWER_WORKER),
            'scansOnFile' => $scans->map(fn (Document $document) => IdentityDocumentData::fromDocument($document))->values()->all(),
            'canRespond' => Gate::allows('respond', $mobilityCase),
            'maxSizeMb' => StoreIdentityDocumentRequest::MAX_KB / 1024,
        ]);
    }
}
