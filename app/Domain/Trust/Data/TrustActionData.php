<?php

namespace App\Domain\Trust\Data;

use App\Domain\Trust\Enums\TrustActionType;
use App\Domain\Trust\Enums\TrustReason;
use App\Domain\Trust\Models\Appeal;
use App\Domain\Trust\Models\TrustAction;
use App\Domain\Trust\Support\TrustSubject;
use Spatie\LaravelData\Data;

/**
 * A suspension or restoration with its appeals.
 */
class TrustActionData extends Data
{
    /**
     * @param  list<AppealData>  $appeals
     */
    public function __construct(
        public string $id,
        public TrustActionType $action,
        public TrustReason $reason,
        public string $subject,
        public ?string $note,
        public ?string $actor,
        /** Staff only: the officer who acted can't decide its appeals (D-088). */
        public ?string $actorId,
        public string $at,
        public array $appeals,
    ) {}

    public static function fromAction(TrustAction $action, bool $forStaff = true): self
    {
        return new self(
            id: $action->id,
            action: $action->action,
            reason: $action->reason_code,
            subject: TrustSubject::label($action->subject),
            note: $forStaff ? $action->note : null,
            actor: $forStaff ? $action->actor?->name : null,
            actorId: $forStaff ? $action->actor_id : null,
            at: $action->created_at?->toIso8601String() ?? '',
            appeals: $action->appeals->sortByDesc('created_at')->map(fn (Appeal $appeal) => AppealData::fromAppeal($appeal, $forStaff))->values()->all(),
        );
    }
}
