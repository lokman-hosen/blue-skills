<?php

namespace App\Domain\Institute\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Institute\Enums\AssessmentResult;
use App\Domain\Institute\Enums\IssuedRecordStatus;
use App\Domain\Institute\Models\CohortMember;
use App\Domain\Institute\Models\TradeAssessment;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Support\Notify;
use App\Domain\Verification\Enums\EvidenceType;
use App\Domain\Verification\States\Evidence\IssuerVerified;
use Illuminate\Support\Facades\DB;

/**
 * An assessor records a trade assessment (spec §7). A pass becomes an "issuer verified" item on
 * the Skill Passport; a result that isn't a pass stays on the institute's record and is only
 * told to the worker, so it never counts against them with employers (D-085).
 */
class RecordTradeAssessment
{
    /**
     * @param  array{title: string, result: AssessmentResult, score: int|null, assessed_on: string, note: string|null}  $data
     */
    public function handle(CohortMember $member, array $data, User $actor): TradeAssessment
    {
        $member->loadMissing(['cohort.course', 'cohort.organization', 'workerProfile.user']);

        $assessment = DB::transaction(function () use ($member, $data, $actor): TradeAssessment {
            $assessment = TradeAssessment::query()->create([
                ...$data,
                'organization_id' => $member->cohort->organization_id,
                'cohort_member_id' => $member->id,
                'worker_profile_id' => $member->worker_profile_id,
                'occupation_id' => $member->cohort->course->occupation_id,
                'status' => IssuedRecordStatus::Issued,
                'assessed_by' => $actor->id,
            ]);

            if ($assessment->result === AssessmentResult::Pass) {
                $assessment->evidence()->create([
                    'worker_profile_id' => $member->worker_profile_id,
                    'type' => EvidenceType::TradeAssessment,
                    'status' => IssuerVerified::class,
                    'verified_by' => $actor->id,
                    'verified_at' => now(),
                ]);
            }

            activity('institute')->performedOn($assessment)->causedBy($actor)
                ->withProperties(['member' => $member->id, 'result' => $assessment->result->value, 'score' => $assessment->score])
                ->log('assessment.recorded');

            return $assessment;
        });

        $user = $member->workerProfile?->user;

        if ($user !== null) {
            Notify::user($user, NotificationEvent::InstituteRecord, [
                'institute' => (string) $member->cohort->organization->display_name,
                'title' => $assessment->title,
                'record_action' => $assessment->result === AssessmentResult::Pass ? 'passed' : 'not_passed',
            ], route('worker.passport.show', absolute: false));
        }

        return $assessment;
    }
}
