<?php

namespace App\Domain\Mobility\Support;

use App\Domain\Identity\Models\User;
use App\Domain\Mobility\Enums\CaseEventAction;
use App\Domain\Mobility\Models\CaseDocument;

/**
 * Every checklist change is a case event both sides see and an activity-log entry with before
 * and after values (CLAUDE.md data conventions).
 */
final class CaseDocumentLog
{
    /**
     * @param  array<string, mixed>  $old
     */
    public static function record(CaseDocument $item, CaseEventAction $action, User $actor, ?string $note = null, array $old = []): void
    {
        $item->mobilityCase->events()->create([
            'action' => $action,
            'case_document_id' => $item->id,
            'actor_user_id' => $actor->id,
            'note' => $note,
        ]);

        activity('mobility')->performedOn($item->mobilityCase)->causedBy($actor)
            ->withProperties([
                'document' => $item->id,
                'type' => $item->type->value,
                'old' => $old,
                'attributes' => ['status' => $item->status->value, 'shared' => $item->isShared(), 'employer_can_view' => $item->employer_can_view],
                'note' => $note,
            ])
            ->log("mobility_case.{$action->value}");
    }
}
