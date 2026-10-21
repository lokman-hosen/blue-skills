<?php

namespace App\Domain\Mobility\Actions;

use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
use App\Domain\Mobility\Enums\CaseDocumentStatus;
use App\Domain\Mobility\Enums\CaseEventAction;
use App\Domain\Mobility\Models\CaseDocument;
use App\Domain\Mobility\Support\CaseChecklist;
use App\Domain\Mobility\Support\CaseDocumentLog;
use App\Domain\Verification\Models\Document;
use App\Domain\Worker\Actions\AddIdentityDocument;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The worker shares a file for one checklist item with the case (product rule 6): a new upload,
 * or for a passport or ID the scan already on their Documents page. Sharing is per item, recorded
 * and revocable; the worker chooses whether the employer of an agency job sees it (D-079).
 */
class ShareCaseDocument
{
    public function __construct(private readonly AddIdentityDocument $addIdentity) {}

    public function handle(CaseDocument $item, WorkerProfile $profile, UploadedFile|Document $file, bool $employerCanView): CaseDocument
    {
        if (! $item->fromWorker() || $item->status === CaseDocumentStatus::Accepted) {
            throw ValidationException::withMessages(['document' => __('mobility.document.errors.not_sharable')]);
        }

        if ($file instanceof Document && ($file->owner_user_id !== $profile->user_id || $file->type !== $item->type)) {
            throw ValidationException::withMessages(['document_id' => __('mobility.document.errors.not_yours')]);
        }

        $old = ['status' => $item->status->value, 'document' => $item->document_id, 'shared' => $item->isShared()];
        $previous = $item->document;

        DB::transaction(function () use ($item, $profile, $file, $employerCanView, $previous, $old): void {
            $document = $file instanceof Document ? $file : $this->store($item, $profile, $file);

            $item->update([
                'document_id' => $document->id,
                'status' => CaseDocumentStatus::Uploaded,
                'employer_can_view' => $employerCanView,
                'shared_at' => now(),
                'revoked_at' => null,
                'reason_code' => null,
                'decided_by' => null,
                'decided_at' => null,
            ]);

            // A file made only for this item goes when it is replaced; scans on the Documents
            // page stay there.
            if ($previous !== null && ! $previous->is($document) && ! in_array($previous->type, CaseChecklist::IDENTITY, true)) {
                $previous->clearMediaCollection(Document::FILE_COLLECTION);
                $previous->delete();
            }

            CaseDocumentLog::record($item, CaseEventAction::DocumentUploaded, $profile->user, null, $old);
        });

        Notify::caseManagers($item->mobilityCase, NotificationEvent::CaseDocumentAnswered, [
            'document' => $item->title,
            'document_action' => 'shared',
        ]);

        return $item;
    }

    /**
     * Passport and ID scans become the worker's scan on file, like the Documents page (D-047).
     */
    private function store(CaseDocument $item, WorkerProfile $profile, UploadedFile $file): Document
    {
        if (in_array($item->type, CaseChecklist::IDENTITY, true)) {
            return $this->addIdentity->handle($profile, $item->type, null, $file);
        }

        $document = Document::query()->create([
            'owner_user_id' => $profile->user_id,
            'type' => $item->type,
            'title' => $item->title,
        ]);

        $document->addMedia($file)
            ->usingName($file->getClientOriginalName())
            ->usingFileName($item->type->value.'.'.($file->guessExtension() ?? 'pdf'))
            ->toMediaCollection(Document::FILE_COLLECTION);

        return $document;
    }
}
