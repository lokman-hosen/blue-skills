<?php

namespace App\Domain\Trust\Data;

use App\Domain\Identity\Models\User;
use Spatie\Activitylog\Models\Activity;
use Spatie\LaravelData\Data;

/**
 * One activity-log entry in the staff audit log (spec §10): who did what to which record, with
 * the before and after values the actions recorded.
 */
class AuditEntryData extends Data
{
    public function __construct(
        public int|string $id,
        public string $at,
        public ?string $area,
        public string $event,
        public ?string $actor,
        public ?string $actorEmail,
        public ?string $subjectType,
        public ?string $subjectId,
        public string $properties,
    ) {}

    public static function fromActivity(Activity $activity): self
    {
        $causer = $activity->causer;

        return new self(
            id: $activity->getKey(),
            at: $activity->created_at?->toIso8601String() ?? '',
            area: $activity->log_name,
            event: $activity->description,
            actor: $causer instanceof User ? $causer->name : null,
            actorEmail: $causer instanceof User ? $causer->email : null,
            subjectType: $activity->subject_type,
            subjectId: $activity->subject_id !== null ? (string) $activity->subject_id : null,
            properties: (string) json_encode($activity->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );
    }
}
