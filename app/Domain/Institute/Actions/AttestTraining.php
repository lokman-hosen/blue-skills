<?php

namespace App\Domain\Institute\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Institute\Enums\IssuedRecordStatus;
use App\Domain\Institute\Models\CohortMember;
use App\Domain\Institute\Models\TrainingAttestation;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
use App\Domain\Verification\Enums\EvidenceType;
use App\Domain\Verification\States\Evidence\IssuerVerified;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The institute attests that a graduate completed the course (spec §7). It becomes its own
 * "issuer verified" training item on the Skill Passport, naming the institute; the worker's own
 * career history is never touched (product rules 9 and 11). One attestation per cohort place.
 */
class AttestTraining
{
    public function handle(CohortMember $member, string $completedOn, ?string $note, User $actor): TrainingAttestation
    {
        if ($member->attestations()->where('status', IssuedRecordStatus::Issued)->exists()) {
            throw ValidationException::withMessages(['attestation' => __('institute.records.already_attested')]);
        }

        $member->loadMissing(['cohort.course', 'cohort.organization', 'workerProfile.user']);

        $attestation = DB::transaction(function () use ($member, $completedOn, $note, $actor): TrainingAttestation {
            $attestation = TrainingAttestation::query()->create([
                'organization_id' => $member->cohort->organization_id,
                'cohort_member_id' => $member->id,
                'worker_profile_id' => $member->worker_profile_id,
                'course_title' => $member->cohort->course->title,
                'completed_on' => $completedOn,
                'note' => $note,
                'status' => IssuedRecordStatus::Issued,
                'issued_by' => $actor->id,
            ]);

            $attestation->evidence()->create([
                'worker_profile_id' => $member->worker_profile_id,
                'type' => EvidenceType::Training,
                'status' => IssuerVerified::class,
                'verified_by' => $actor->id,
                'verified_at' => now(),
            ]);

            activity('institute')->performedOn($attestation)->causedBy($actor)
                ->withProperties(['member' => $member->id, 'course' => $attestation->course_title, 'completed_on' => $completedOn])
                ->log('training.attested');

            return $attestation;
        });

        $this->notify($member, $attestation->course_title, 'attested');

        return $attestation;
    }

    private function notify(CohortMember $member, string $title, string $action): void
    {
        $user = $member->workerProfile?->user;

        if ($user !== null) {
            Notify::user($user, NotificationEvent::InstituteRecord, [
                'institute' => (string) $member->cohort->organization->display_name,
                'title' => $title,
                'record_action' => $action,
            ], route('worker.passport.show', absolute: false));
        }
    }
}
