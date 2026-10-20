<?php

namespace App\Domain\Worker\Actions;

use App\Domain\Verification\Models\Document;
use App\Domain\Verification\Models\EvidenceItem;
use Illuminate\Support\Facades\DB;

/**
 * The worker withdraws a credential. The document is soft deleted for the audit trail and its
 * file removed.
 */
class RemoveWorkerCredential
{
    public function handle(EvidenceItem $evidence): void
    {
        DB::transaction(function () use ($evidence): void {
            $document = $evidence->documentable;

            activity('worker')->performedOn($evidence->workerProfile)->causedBy($evidence->workerProfile->user)
                ->withProperties(['evidence' => $evidence->id, 'type' => $evidence->type->value])
                ->log('credential.removed');

            $evidence->verificationRequests()->delete();
            $evidence->delete();

            if ($document instanceof Document) {
                $document->clearMediaCollection(Document::FILE_COLLECTION);
                $document->delete();
            }
        });
    }
}
