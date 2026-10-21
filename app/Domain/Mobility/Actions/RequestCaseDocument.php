<?php

namespace App\Domain\Mobility\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
use App\Domain\Mobility\Enums\CaseDocumentStatus;
use App\Domain\Mobility\Enums\CaseEventAction;
use App\Domain\Mobility\Enums\DocumentProvider;
use App\Domain\Mobility\Models\CaseDocument;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Mobility\Support\CaseChecklist;
use App\Domain\Mobility\Support\CaseDocumentLog;
use App\Domain\Verification\Enums\DocumentType;
use Illuminate\Support\Facades\DB;

/**
 * Adds an item to the case checklist beyond the destination's list (D-079). Items the worker
 * provides alert the worker.
 */
class RequestCaseDocument
{
    public function handle(MobilityCase $case, DocumentType $type, ?string $title, DocumentProvider $provider, ?string $dueOn, ?string $note, User $actor): CaseDocument
    {
        $item = DB::transaction(function () use ($case, $type, $title, $provider, $dueOn, $note, $actor): CaseDocument {
            $item = $case->documents()->create([
                'type' => $type,
                'title' => $title ?? CaseChecklist::title($type),
                'status' => CaseDocumentStatus::Requested,
                'provided_by' => $provider,
                'employer_can_view' => CaseChecklist::employerCanViewByDefault($type),
                'note' => $note,
                'due_on' => $dueOn,
                'requested_by' => $actor->id,
            ]);
            $item->setRelation('mobilityCase', $case);
            CaseDocumentLog::record($item, CaseEventAction::DocumentRequested, $actor, $note);

            return $item;
        });

        if ($provider === DocumentProvider::Worker) {
            Notify::worker($case->application, NotificationEvent::CaseDocument, [
                'reference' => (string) $case->reference,
                'document' => $item->title,
                'document_action' => 'requested',
            ], route('worker.cases.show', $case, false));
        }

        return $item;
    }
}
