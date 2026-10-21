<?php

namespace App\Domain\Worker\Actions;

use App\Domain\Applications\Enums\ConsentPurpose;
use App\Domain\Applications\Models\ApplicationConsent;
use App\Domain\Verification\Models\Document;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Support\Facades\DB;

/**
 * Deleting a scan ends every share of it: the application consents that point at it and their
 * document-share consents are revoked (kept for the history), then the file is removed.
 */
class RemoveIdentityDocument
{
    public function handle(WorkerProfile $profile, Document $document): void
    {
        DB::transaction(function () use ($profile, $document): void {
            $now = now();
            $shares = ApplicationConsent::query()->active()->where('document_id', $document->id)->get();

            ApplicationConsent::query()->whereKey($shares->modelKeys())->update(['revoked_at' => $now]);
            $profile->consents()->active()
                ->where('purpose', ConsentPurpose::DocumentShare)
                ->where('scope->document', $document->id)
                ->update(['revoked_at' => $now]);

            $document->evidenceItems()->get()->each(function (EvidenceItem $evidence): void {
                $evidence->verificationRequests()->delete();
                $evidence->delete();
            });

            activity('worker')->performedOn($profile)->causedBy($profile->user)
                ->withProperties(['document' => $document->id, 'type' => $document->type->value, 'revoked_shares' => $shares->count()])
                ->log('identity_document.removed');

            $document->clearMediaCollection(Document::FILE_COLLECTION);
            $document->delete();
        });
    }
}
