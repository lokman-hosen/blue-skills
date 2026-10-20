<?php

namespace App\Domain\Worker\Data;

use App\Domain\Applications\Enums\ConsentPurpose;
use App\Domain\Applications\Models\Consent;
use Spatie\LaravelData\Data;

/**
 * One row of the worker's consent history: what was granted, when, and whether it was revoked.
 */
class ConsentRecordData extends Data
{
    public function __construct(
        public string $id,
        public ConsentPurpose $purpose,
        public string $grantedAt,
        public ?string $revokedAt,
    ) {}

    public static function fromConsent(Consent $consent): self
    {
        return new self(
            id: $consent->id,
            purpose: $consent->purpose,
            grantedAt: $consent->granted_at->toIso8601String(),
            revokedAt: $consent->revoked_at?->toIso8601String(),
        );
    }
}
