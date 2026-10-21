<?php

namespace App\Domain\Worker\Actions;

use App\Domain\Applications\Enums\ConsentPurpose;
use App\Domain\Applications\Models\ApplicationConsent;
use App\Domain\Mobility\Enums\CaseDocumentStatus;
use App\Domain\Mobility\Enums\CaseEventAction;
use App\Domain\Mobility\Models\CaseDocument;
use App\Domain\Mobility\Support\CaseDocumentLog;
use App\Domain\Verification\Models\Document;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Support\Facades\DB;

/**
 * Deleting a scan ends every share of it: the application consents that point at it, their
 * document-share consents and mobility case items sharing it are revoked (kept for the history),
 * then the file is removed.
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

            CaseDocument::query()->where('document_id', $document->id)->whereNull('revoked_at')->whereNotNull('shared_at')->get()
                ->each(function (CaseDocument $item) use ($profile, $now): void {
                    $old = ['status' => $item->status->value, 'shared' => true];
                    $item->update(['revoked_at' => $now, 'status' => CaseDocumentStatus::Requested, 'decided_by' => null, 'decided_at' => null]);
                    CaseDocumentLog::record($item, CaseEventAction::DocumentRevoked, $profile->user, null, $old);
                });

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
