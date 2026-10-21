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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The worker confirms the contract the organization sent, or sends it back with what needs
 * changing (D-079). Confirming records who and when; the organization still moves the stage.
 */
class AnswerCaseContract
{
    public const ANSWERS = ['confirm', 'query'];

    public function handle(CaseDocument $item, string $answer, ?string $note, User $worker): void
    {
        if ($item->type !== DocumentType::Contract || $item->fromWorker() || $item->status !== CaseDocumentStatus::Uploaded) {
            throw ValidationException::withMessages(['contract' => __('mobility.document.errors.no_contract')]);
        }

        $confirm = $answer === 'confirm';
        $old = ['status' => $item->status->value];

        DB::transaction(function () use ($item, $confirm, $note, $worker, $old): void {
            $item->update([
                'status' => $confirm ? CaseDocumentStatus::Accepted : CaseDocumentStatus::Returned,
                'note' => $confirm ? $item->note : $note,
                'reason_code' => null,
                'decided_by' => $worker->id,
                'decided_at' => now(),
            ]);

            CaseDocumentLog::record($item, $confirm ? CaseEventAction::ContractConfirmed : CaseEventAction::DocumentReturned, $worker, $confirm ? null : $note, $old);
        });

        Notify::caseManagers($item->mobilityCase, NotificationEvent::CaseDocumentAnswered, [
            'document' => $item->title,
            'document_action' => $confirm ? 'contract_confirmed' : 'contract_queried',
        ]);
    }
}
