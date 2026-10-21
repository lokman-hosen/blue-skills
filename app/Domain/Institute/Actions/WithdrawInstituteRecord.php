<?php

namespace App\Domain\Institute\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Institute\Enums\IssuedRecordStatus;
use App\Domain\Institute\Models\TradeAssessment;
use App\Domain\Institute\Models\TrainingAttestation;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
use App\Domain\Verification\States\Evidence\Rejected;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The issuing institute withdraws an attestation or assessment with a reason (D-082). Its
 * evidence item shows as rejected ("withdrawn by the issuer") and the worker is told.
 */
class WithdrawInstituteRecord
{
    public const REASON_CODE = 'withdrawn_by_issuer';

    public function handle(TrainingAttestation|TradeAssessment $record, string $reason, User $actor): void
    {
        if ($record->status !== IssuedRecordStatus::Issued) {
            throw ValidationException::withMessages(['reason' => __('institute.records.already_withdrawn')]);
        }

        DB::transaction(function () use ($record, $reason, $actor): void {
            $record->update([
                'status' => IssuedRecordStatus::Withdrawn,
                'withdrawn_by' => $actor->id,
                'withdrawn_at' => now(),
                'withdrawn_reason' => $reason,
            ]);

            $evidence = $record->evidence;

            if ($evidence !== null && ! $evidence->status->equals(Rejected::class)) {
                $evidence->status->transitionTo(Rejected::class);
                $evidence->update(['reason_code' => self::REASON_CODE]);
            }

            activity('institute')->performedOn($record)->causedBy($actor)
                ->withProperties(['old' => ['status' => IssuedRecordStatus::Issued->value], 'attributes' => ['status' => IssuedRecordStatus::Withdrawn->value], 'reason' => $reason])
                ->log($record instanceof TrainingAttestation ? 'training.withdrawn' : 'assessment.withdrawn');
        });

        $user = $record->workerProfile->user;

        if ($user !== null) {
            Notify::user($user, NotificationEvent::InstituteRecord, [
                'institute' => (string) $record->organization->display_name,
                'title' => $record instanceof TrainingAttestation ? $record->course_title : $record->title,
                'record_action' => 'withdrawn',
            ], route('worker.passport.show', absolute: false));
        }
    }
}
