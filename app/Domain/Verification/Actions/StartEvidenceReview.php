<?php

namespace App\Domain\Verification\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Verification\Enums\VerificationType;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Verification\Models\VerificationRequest;
use App\Domain\Verification\States\Evidence\UploadedUnreviewed;
use App\Domain\Verification\States\Evidence\VerificationPending;
use App\Domain\Verification\States\VerificationRequest\InReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * An officer takes a worker's uploaded evidence for review (D-090): it becomes "verification
 * pending" and is assigned to them. Only from here can staff open the file.
 */
class StartEvidenceReview
{
    public function handle(EvidenceItem $item, User $officer): VerificationRequest
    {
        if (! $item->status->equals(UploadedUnreviewed::class, VerificationPending::class)) {
            throw ValidationException::withMessages(['evidence' => __('admin.evidence.not_reviewable')]);
        }

        return DB::transaction(function () use ($item, $officer): VerificationRequest {
            if ($item->status->equals(UploadedUnreviewed::class)) {
                $item->status->transitionTo(VerificationPending::class);
            }

            /** @var VerificationRequest $request */
            $request = $item->verificationRequests()->whereIn('status', ['open', 'in_review'])->first()
                ?? $item->verificationRequests()->create([
                    'type' => VerificationType::WorkerEvidence,
                    'status' => InReview::class,
                ]);
            $request->update(['assigned_to' => $officer->id]);

            activity('verification')->performedOn($item)->causedBy($officer)
                ->withProperties(['type' => $item->type->value])
                ->log('evidence.review_started');

            return $request;
        });
    }
}
