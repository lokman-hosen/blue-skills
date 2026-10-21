<?php

namespace App\Domain\Mobility\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
use App\Domain\Mobility\Enums\CaseDocumentStatus;
use App\Domain\Mobility\Enums\CaseEventAction;
use App\Domain\Mobility\Models\CaseDocument;
use App\Domain\Mobility\Support\CaseDocumentLog;
use App\Domain\Verification\Enums\DocumentType;
use App\Domain\Verification\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The managing organization adds a file it provides (contract, work permit, visa). The worker
 * always sees it. A contract waits for the worker to confirm it; anything else is ready at once.
 */
class UploadCaseDocument
{
    public function handle(CaseDocument $item, UploadedFile $file, bool $employerCanView, User $actor): CaseDocument
    {
        if ($item->fromWorker() || $item->status === CaseDocumentStatus::Accepted) {
            throw ValidationException::withMessages(['document' => __('mobility.document.errors.not_uploadable')]);
        }

        $old = ['status' => $item->status->value, 'document' => $item->document_id];
        $contract = $item->type === DocumentType::Contract;

        DB::transaction(function () use ($item, $file, $employerCanView, $actor, $contract, $old): void {
            $previous = $item->document;
            $document = Document::query()->create([
                'owner_user_id' => $actor->id,
                'type' => $item->type,
                'title' => $item->title,
            ]);
            $document->addMedia($file)
                ->usingName($file->getClientOriginalName())
                ->usingFileName($item->type->value.'.'.($file->guessExtension() ?? 'pdf'))
                ->toMediaCollection(Document::FILE_COLLECTION);

            $item->update([
                'document_id' => $document->id,
                'status' => $contract ? CaseDocumentStatus::Uploaded : CaseDocumentStatus::Accepted,
                'employer_can_view' => $employerCanView,
                'shared_at' => now(),
                'revoked_at' => null,
                'reason_code' => null,
                'decided_by' => $contract ? null : $actor->id,
                'decided_at' => $contract ? null : now(),
            ]);

            if ($previous !== null) {
                $previous->clearMediaCollection(Document::FILE_COLLECTION);
                $previous->delete();
            }

            CaseDocumentLog::record($item, CaseEventAction::DocumentUploaded, $actor, null, $old);
        });

        Notify::worker($item->mobilityCase->application, NotificationEvent::CaseDocument, [
            'reference' => (string) $item->mobilityCase->reference,
            'document' => $item->title,
            'document_action' => $contract ? 'contract_sent' : 'added',
        ], route('worker.cases.show', $item->mobilityCase, false));

        return $item;
    }
}
