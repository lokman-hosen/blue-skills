<?php

namespace App\Domain\Applications\Data;

use App\Domain\Applications\Models\ApplicationStageEvent;
use Spatie\LaravelData\Data;

class StageEventData extends Data
{
    public function __construct(
        public string $id,
        public ?string $from,
        public string $to,
        public string $at,
        public ?string $reasonCode,
        /** Organization views only; workers see the organization, not the person. */
        public ?string $actorName,
        /** Internal note; never sent to the worker. */
        public ?string $note,
    ) {}

    public static function forWorker(ApplicationStageEvent $event): self
    {
        return new self($event->id, $event->from_status, $event->to_status, $event->created_at?->toIso8601String() ?? '', $event->reason_code, null, null);
    }

    public static function forOrganization(ApplicationStageEvent $event): self
    {
        return new self($event->id, $event->from_status, $event->to_status, $event->created_at?->toIso8601String() ?? '', $event->reason_code, $event->actor?->name, $event->note);
    }
}
