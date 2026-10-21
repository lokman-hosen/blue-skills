<?php

namespace App\Domain\Mobility\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
use App\Domain\Mobility\Enums\CaseDocumentStatus;
use App\Domain\Mobility\Enums\CaseEventAction;
use App\Domain\Mobility\Models\CaseDocument;
use App\Domain\Mobility\Support\CaseDocumentLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The worker stops sharing a file with the case (product rule 6). The organizations stop seeing
 * it at once and the item is needed again; the share times stay for the history.
 */
class RevokeCaseDocument
{
    public function handle(CaseDocument $item, User $worker): void
    {
        if (! $item->fromWorker() || ! $item->isShared()) {
            throw ValidationException::withMessages(['document' => __('mobility.document.errors.not_shared')]);
        }

        $old = ['status' => $item->status->value, 'shared' => true];

        DB::transaction(function () use ($item, $worker, $old): void {
            $item->update(['revoked_at' => now(), 'status' => CaseDocumentStatus::Requested, 'decided_by' => null, 'decided_at' => null]);
            CaseDocumentLog::record($item, CaseEventAction::DocumentRevoked, $worker, null, $old);
        });

        Notify::caseManagers($item->mobilityCase, NotificationEvent::CaseDocumentAnswered, [
            'document' => $item->title,
            'document_action' => 'revoked',
        ]);
    }
}
