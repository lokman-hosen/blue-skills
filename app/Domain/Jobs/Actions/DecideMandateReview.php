<?php

namespace App\Domain\Jobs\Actions;

use App\Domain\Identity\Enums\Permission;
use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Enums\MandateStatus;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
use App\Domain\Verification\Actions\RecordReviewDecision;
use App\Domain\Verification\Enums\ReviewReason;
use App\Domain\Verification\Enums\VerificationDecision;
use App\Domain\Verification\Models\VerificationRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Staff verify a confirmed mandate: the letter and the agency licence (product rule 10).
 * Approving re-checks that the licence is valid for the destination.
 */
class DecideMandateReview
{
    public function __construct(private readonly RecordReviewDecision $record) {}

    public function handle(VerificationRequest $review, User $reviewer, VerificationDecision $decision, ?ReviewReason $reason, ?string $note): JobMandate
    {
        $mandate = $review->subject;
        abort_unless($mandate instanceof JobMandate, 404);

        if ($mandate->status !== MandateStatus::Pending) {
            throw ValidationException::withMessages(['decision' => __('mandates.not_ready_for_review')]);
        }

        if ($decision === VerificationDecision::Approved && ! $mandate->agency->hasValidLicence($mandate->destination_country_code)) {
            throw ValidationException::withMessages(['decision' => __('mandates.licence_not_valid')]);
        }

        DB::transaction(function () use ($review, $mandate, $reviewer, $decision, $reason, $note): void {
            $this->record->handle($review, $reviewer, $decision, $reason, $note);

            match ($decision) {
                VerificationDecision::Approved => $mandate->update(['status' => MandateStatus::Verified]),
                VerificationDecision::Rejected => $mandate->update(['status' => MandateStatus::Rejected]),
                // Stays pending while the agency adds what was asked for.
                VerificationDecision::NeedsInfo => null,
            };
        });

        foreach (Notify::members($mandate->agency, Permission::MandatesManage) as $member) {
            Notify::user($member, NotificationEvent::MandateReviewed, [
                'reference' => $mandate->reference,
                'employer' => $mandate->employer->display_name,
                'decision' => $decision->value,
            ], route('org.mandates.show', $mandate, false));
        }

        return $mandate;
    }
}
