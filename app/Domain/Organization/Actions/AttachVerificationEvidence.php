<?php

namespace App\Domain\Organization\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Verification\Models\VerificationRequest;
use App\Domain\Verification\States\VerificationRequest\InReview;
use App\Domain\Verification\States\VerificationRequest\NeedsInfo;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AttachVerificationEvidence
{
    /**
     * Stores a registration or licence document privately on the open verification request.
     */
    public function handle(VerificationRequest $request, UploadedFile $file, User $uploader): Media
    {
        $media = $request->addMedia($file)
            ->usingName($file->getClientOriginalName())
            ->withCustomProperties(['uploaded_by' => $uploader->id])
            ->toMediaCollection(VerificationRequest::EVIDENCE_COLLECTION);

        // The organization answered a request for more information: back to the reviewer.
        if ($request->status->equals(NeedsInfo::class)) {
            $request->status->transitionTo(InReview::class);
        }

        activity('organization')->performedOn($request)->causedBy($uploader)
            ->withProperties(['file' => $media->file_name])
            ->log('verification.evidence_added');

        return $media;
    }
}
