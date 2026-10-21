<?php

namespace App\Domain\Mobility\Data;

use App\Domain\Identity\Models\User;
use App\Domain\Mobility\Enums\CaseEventAction;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Mobility\Models\MobilityCaseEvent;
use App\Domain\Mobility\Support\StageTemplate;
use Spatie\LaravelData\Data;

/**
 * One line of the case history. Workers see the organization's name, not the staff member's.
 */
class CaseEventData extends Data
{
    public function __construct(
        public string $id,
        /** Translated via `mobility.event.{action}`. */
        public CaseEventAction $action,
        public ?string $stage,
        /** The checklist item a document event is about. */
        public ?string $document,
        public ?string $actor,
        public ?string $note,
        public string $at,
    ) {}

    public static function fromEvent(MobilityCaseEvent $event, MobilityCase $case, bool $forWorker): self
    {
        $actor = $event->actor;

        return new self(
            id: $event->id,
            action: $event->action,
            stage: $event->stage_key !== null ? StageTemplate::label($case->template, $event->stage_key) : null,
            document: $event->caseDocument !== null ? CaseDocumentData::title($event->caseDocument) : null,
            actor: match (true) {
                ! $actor instanceof User => null,
                $actor->isWorker() => $actor->name,
                $forWorker => $case->managingOrganization?->display_name,
                default => $actor->name,
            },
            note: $event->note,
            at: $event->created_at?->toIso8601String() ?? '',
        );
    }
}
