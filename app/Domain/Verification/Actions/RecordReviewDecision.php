<?php

namespace App\Domain\Verification\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Verification\Enums\ReviewReason;
use App\Domain\Verification\Enums\VerificationDecision;
use App\Domain\Verification\Models\VerificationRequest;
use App\Domain\Verification\States\VerificationRequest\Approved;
use App\Domain\Verification\States\VerificationRequest\InReview;
use App\Domain\Verification\States\VerificationRequest\NeedsInfo;
use App\Domain\Verification\States\VerificationRequest\Open;
use App\Domain\Verification\States\VerificationRequest\Rejected;
use Illuminate\Validation\ValidationException;

/**
 * Closes (or pauses, for "more information") one staff review with decision, reason code,
 * note and reviewer. The model logs the before and after values (LogsStatusChanges).
 */
class RecordReviewDecision
{
    public function handle(VerificationRequest $request, User $reviewer, VerificationDecision $decision, ?ReviewReason $reason, ?string $notes): VerificationRequest
    {
        if (! $request->status->equals(Open::class, InReview::class)) {
            throw ValidationException::withMessages(['decision' => __('admin.review.not_open')]);
        }

        if ($decision !== VerificationDecision::Approved && $reason === null) {
            throw ValidationException::withMessages(['reason' => __('admin.review.reason_required')]);
        }

        if (! $request->status->equals(InReview::class)) {
            $request->status->transitionTo(InReview::class);
        }

        $request->status->transitionTo(match ($decision) {
            VerificationDecision::Approved => Approved::class,
            VerificationDecision::Rejected => Rejected::class,
            VerificationDecision::NeedsInfo => NeedsInfo::class,
        });

        $request->update([
            'decision' => $decision,
            'reason_code' => $reason?->value,
            // `notes` holds intake flags (e.g. free webmail); the reviewer's message is separate.
            'decision_note' => $notes,
            'decided_by' => $reviewer->id,
            'decided_at' => now(),
        ]);

        activity('verification')->performedOn($request)->causedBy($reviewer)
            ->withProperties(['decision' => $decision->value, 'reason_code' => $reason?->value, 'notes' => $notes])
            ->log("review.{$decision->value}");

        return $request;
    }
}
