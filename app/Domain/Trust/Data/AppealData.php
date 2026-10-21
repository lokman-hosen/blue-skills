<?php

namespace App\Domain\Trust\Data;

use App\Domain\Trust\Models\Appeal;
use App\Support\Data\StatusData;
use Spatie\LaravelData\Data;

class AppealData extends Data
{
    public function __construct(
        public string $id,
        /** Translated via `status.appeal_{value}`. */
        public StatusData $status,
        public string $statement,
        public ?string $appellant,
        public ?string $decisionNote,
        public ?string $decider,
        public string $submittedAt,
        public ?string $decidedAt,
    ) {}

    public static function fromAppeal(Appeal $appeal, bool $forStaff = true): self
    {
        return new self(
            id: $appeal->id,
            status: new StatusData('appeal_'.$appeal->status->value, $appeal->status->label(), $appeal->status->tone()),
            statement: $appeal->statement,
            appellant: $appeal->appellant?->name,
            decisionNote: $appeal->decision_note,
            decider: $forStaff ? $appeal->decider?->name : null,
            submittedAt: $appeal->created_at?->toIso8601String() ?? '',
            decidedAt: $appeal->decided_at?->toIso8601String(),
        );
    }
}
