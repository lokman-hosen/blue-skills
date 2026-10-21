<?php

namespace App\Domain\Institute\Data;

use App\Domain\Institute\Enums\IssuedRecordStatus;
use App\Domain\Institute\Models\TradeAssessment;
use App\Domain\Institute\Models\TrainingAttestation;
use App\Support\Data\StatusData;
use Spatie\LaravelData\Data;

/**
 * An attestation or assessment as the issuing institute sees it in its graduates table.
 */
class IssuedRecordData extends Data
{
    public function __construct(
        public string $id,
        public string $title,
        /** Translated via `status.{value}`: record_issued, record_withdrawn, assessment_pass, assessment_fail. */
        public StatusData $status,
        public string $date,
        public ?int $score,
        public bool $withdrawn,
        public ?string $withdrawnReason,
    ) {}

    public static function fromAttestation(TrainingAttestation $attestation): self
    {
        $withdrawn = $attestation->status === IssuedRecordStatus::Withdrawn;

        return new self(
            id: $attestation->id,
            title: $attestation->course_title,
            status: $withdrawn
                ? new StatusData('record_withdrawn', IssuedRecordStatus::Withdrawn->label(), 'bad')
                : new StatusData('record_attested', 'Attested', 'ok'),
            date: $attestation->completed_on->toDateString(),
            score: null,
            withdrawn: $withdrawn,
            withdrawnReason: $attestation->withdrawn_reason,
        );
    }

    public static function fromAssessment(TradeAssessment $assessment): self
    {
        $withdrawn = $assessment->status === IssuedRecordStatus::Withdrawn;

        return new self(
            id: $assessment->id,
            title: $assessment->title,
            status: $withdrawn
                ? new StatusData('record_withdrawn', IssuedRecordStatus::Withdrawn->label(), 'bad')
                : new StatusData('assessment_'.$assessment->result->value, $assessment->result->label(), $assessment->result->tone()),
            date: $assessment->assessed_on->toDateString(),
            score: $assessment->score,
            withdrawn: $withdrawn,
            withdrawnReason: $assessment->withdrawn_reason,
        );
    }
}
