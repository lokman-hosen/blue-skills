<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Organization\Actions\AttachVerificationEvidence;
use App\Domain\Organization\Data\EvidenceDocumentData;
use App\Domain\Organization\Data\OrganizationSummaryData;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Support\CurrentOrganization;
use App\Domain\Verification\Models\VerificationRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreVerificationEvidenceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Registration evidence for the current organization's open verification request.
 */
class VerificationEvidenceController extends Controller
{
    public function __construct(private readonly CurrentOrganization $currentOrganization) {}

    public function edit(Request $request): Response|RedirectResponse
    {
        $organization = $this->organization($request);

        if ($organization === null) {
            return to_route('organizations.create');
        }

        Gate::authorize('update', $organization);
        $verificationRequest = $organization->openVerificationRequest();

        return Inertia::render('onboarding/evidence', [
            'organization' => OrganizationSummaryData::fromOrganization($organization),
            'documents' => $verificationRequest?->getMedia(VerificationRequest::EVIDENCE_COLLECTION)
                ->map(fn (Media $media) => EvidenceDocumentData::fromMedia($media))
                ->values()
                ->all() ?? [],
            'reviewOpen' => $verificationRequest !== null,
            'maxSizeMb' => (int) (VerificationRequest::EVIDENCE_MAX_KB / 1024),
        ]);
    }

    public function store(StoreVerificationEvidenceRequest $request, AttachVerificationEvidence $attach): RedirectResponse
    {
        $organization = $this->organization($request);
        abort_if($organization === null, 404);
        Gate::authorize('update', $organization);

        $verificationRequest = $organization->openVerificationRequest();
        abort_if($verificationRequest === null, 409, __('organization.no_open_review'));

        $file = $request->file('document');
        abort_unless($file instanceof UploadedFile, 422);
        $attach->handle($verificationRequest, $file, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('organization.evidence_added')]);

        return back();
    }

    private function organization(Request $request): ?Organization
    {
        return $this->currentOrganization->resolve($request->user(), $request->session());
    }
}
