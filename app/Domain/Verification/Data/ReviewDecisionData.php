<?php

namespace App\Domain\Verification\Data;

use App\Domain\Verification\Enums\VerificationDecision;
use App\Domain\Verification\Models\VerificationRequest;
use Spatie\LaravelData\Data;

/**
 * A decided review as the reviewed party sees it: outcome, reason code and the reviewer's note.
 */
class ReviewDecisionData extends Data
{
    public function __construct(
        public VerificationDecision $decision,
        public ?string $reasonCode,
        public ?string $note,
        public ?string $decidedAt,
        public ?string $reviewerName,
    ) {}

    public static function fromRequest(VerificationRequest $request, bool $withReviewer = false): ?self
    {
        if ($request->decision === null) {
            return null;
        }

        return new self(
            decision: $request->decision,
            reasonCode: $request->reason_code,
            note: $request->decision_note,
            decidedAt: $request->decided_at?->toIso8601String(),
            reviewerName: $withReviewer ? $request->decider?->name : null,
        );
    }
}
