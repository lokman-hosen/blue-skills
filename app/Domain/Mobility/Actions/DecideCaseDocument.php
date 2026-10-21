<?php

namespace App\Domain\Mobility\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
use App\Domain\Mobility\Enums\CaseDocumentReturnReason;
use App\Domain\Mobility\Enums\CaseDocumentStatus;
use App\Domain\Mobility\Enums\CaseEventAction;
use App\Domain\Mobility\Models\CaseDocument;
use App\Domain\Mobility\Support\CaseDocumentLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The managing organization checks a file the worker shared: accept it, or send it back with a
 * reason code the worker sees with how to fix it. A person decides; nothing is automatic
 * (product rule 14).
 */
class DecideCaseDocument
{
    public const DECISIONS = ['accept', 'return'];

    public function handle(CaseDocument $item, string $decision, ?CaseDocumentReturnReason $reason, ?string $note, User $actor): void
    {
        if (! $item->fromWorker() || $item->status !== CaseDocumentStatus::Uploaded || ! $item->isShared()) {
            throw ValidationException::withMessages(['document' => __('mobility.document.errors.not_waiting')]);
        }

        $old = ['status' => $item->status->value];
        $accept = $decision === 'accept';

        DB::transaction(function () use ($item, $accept, $reason, $note, $actor, $old): void {
            $item->update([
                'status' => $accept ? CaseDocumentStatus::Accepted : CaseDocumentStatus::Returned,
                'reason_code' => $accept ? null : $reason?->value,
                'note' => $accept ? $item->note : $note,
                'decided_by' => $actor->id,
                'decided_at' => now(),
            ]);

            CaseDocumentLog::record($item, $accept ? CaseEventAction::DocumentAccepted : CaseEventAction::DocumentReturned, $actor, $accept ? null : $note, $old);
        });

        Notify::worker($item->mobilityCase->application, NotificationEvent::CaseDocument, [
            'reference' => (string) $item->mobilityCase->reference,
            'document' => $item->title,
            'document_action' => $accept ? 'accepted' : 'returned',
        ], route('worker.cases.show', $item->mobilityCase, false));
    }
}
