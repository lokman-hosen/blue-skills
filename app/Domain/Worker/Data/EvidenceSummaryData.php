<?php

namespace App\Domain\Worker\Data;

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
    ) {}

    public static function fromEvidence(EvidenceItem $item): self
    {
        $document = $item->documentable instanceof Document ? $item->documentable : null;

        /** @var string $state */
        $state = $item->status->getValue();

        return new self(
            id: $item->id,
            type: $item->type,
            title: $document?->title,
            state: $state,
            reasonCode: $item->reason_code,
            expiresOn: $document?->expires_at?->toDateString(),
            removable: $document !== null && in_array($item->type, AddWorkerCredential::TYPES, true),
        );
    }
}
