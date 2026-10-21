<?php

namespace App\Domain\Worker\Data;

use App\Domain\Institute\Models\TradeAssessment;
use App\Domain\Institute\Models\TrainingAttestation;
use App\Domain\Verification\Enums\EvidenceType;
use App\Domain\Verification\Models\Document;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Worker\Actions\AddWorkerCredential;
use Spatie\LaravelData\Data;

/**
 * One evidence item and its own state (product rule 9). Shown to the worker only.
 */
class EvidenceSummaryData extends Data
{
    public function __construct(
        public string $id,
        public EvidenceType $type,
        public ?string $title,
        /** self_declared | uploaded_unreviewed | verification_pending | issuer_verified | expired | rejected */
        public string $state,
        public ?string $reasonCode,
        public ?string $expiresOn,
        /** Credentials the worker uploaded themselves can be withdrawn. */
        public bool $removable,
        /** The institute that issued it (attestations and assessments, D-082). */
        public ?string $issuer = null,
        public ?string $issuedOn = null,
        /** Hidden by the worker from employers; only issuer items can be hidden. */
        public bool $hidden = false,
        public bool $hideable = false,
    ) {}

    public static function fromEvidence(EvidenceItem $item): self
    {
        $subject = $item->documentable;
        $document = $subject instanceof Document ? $subject : null;

        /** @var string $state */
        $state = $item->status->getValue();

        return new self(
            id: $item->id,
            type: $item->type,
            title: match (true) {
                $subject instanceof TrainingAttestation => $subject->course_title,
                $subject instanceof TradeAssessment => $subject->score !== null
                    ? __('evidence.assessment_title', ['title' => $subject->title, 'score' => $subject->score])
                    : $subject->title,
                default => $document?->title,
            },
            state: $state,
            reasonCode: $item->reason_code,
            expiresOn: $document?->expires_at?->toDateString(),
            removable: $document !== null && in_array($item->type, AddWorkerCredential::TYPES, true),
            issuer: $item->isIssuedByInstitute() ? $subject?->organization?->display_name : null,
            issuedOn: match (true) {
                $subject instanceof TrainingAttestation => $subject->completed_on->toDateString(),
                $subject instanceof TradeAssessment => $subject->assessed_on->toDateString(),
                default => null,
            },
            hidden: $item->hidden_at !== null,
            hideable: $item->isIssuedByInstitute(),
        );
    }

    /**
     * Evidence an employer may see: credentials and institute records the worker didn't hide.
     */
    public static function shownToOrganizations(EvidenceItem $item): bool
    {
        return $item->hidden_at === null
            && (in_array($item->type, AddWorkerCredential::TYPES, true) || $item->type === EvidenceType::TradeAssessment);
    }
}
