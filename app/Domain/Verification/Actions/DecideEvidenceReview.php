<?php

namespace App\Domain\Verification\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
use App\Domain\Verification\Enums\ReviewReason;
use App\Domain\Verification\Enums\VerificationDecision;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Verification\Models\VerificationRequest;
use App\Domain\Verification\States\Evidence\IssuerVerified;
use App\Domain\Verification\States\Evidence\Rejected;
use App\Domain\Verification\States\Evidence\VerificationPending;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Verifies or rejects one evidence item on its own (product rule 9), with a reason code the
 * worker sees. A person decides; nothing is automatic (product rule 14).
 */
class DecideEvidenceReview
{
    public function __construct(private readonly RecordReviewDecision $record) {}

    public function handle(EvidenceItem $item, User $officer, VerificationDecision $decision, ?ReviewReason $reason, ?string $note): void
    {
        if (! $item->status->equals(VerificationPending::class)) {
            throw ValidationException::withMessages(['decision' => __('admin.evidence.start_first')]);
        }

        if ($decision === VerificationDecision::NeedsInfo) {
            throw ValidationException::withMessages(['decision' => __('admin.evidence.no_needs_info')]);
        }

        /** @var VerificationRequest|null $request */
        $request = $item->verificationRequests()->whereIn('status', ['open', 'in_review'])->latest()->first();

        if ($request === null) {
            throw ValidationException::withMessages(['decision' => __('admin.evidence.start_first')]);
        }

        DB::transaction(function () use ($item, $officer, $decision, $reason, $note, $request): void {
            $this->record->handle($request, $officer, $decision, $reason, $note);

            $approved = $decision === VerificationDecision::Approved;
            $item->status->transitionTo($approved ? IssuerVerified::class : Rejected::class);
            $item->update([
                'verified_by' => $approved ? $officer->id : null,
                'verified_at' => $approved ? now() : null,
                'reason_code' => $approved ? null : $reason?->value,
            ]);
        });

        $user = $item->workerProfile->user;

        if ($user !== null) {
            Notify::user($user, NotificationEvent::EvidenceReviewed, [
                'evidence_type' => $item->type->value,
                'decision' => $decision->value,
            ], route('worker.passport.edit', ['section' => 'evidence'], false));
        }
    }
}
