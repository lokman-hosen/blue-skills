<?php

namespace App\Domain\Institute\Data;

use App\Domain\Verification\Enums\EvidenceType;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Worker\Models\SkillShow;
use App\Domain\Worker\Models\WorkerProfile;
use Spatie\LaravelData\Data;

/**
 * A graduate in one of the institute's pools, or available to add.
 */
class PoolMemberData extends Data
{
    public function __construct(
        public string $profileId,
        public string $name,
        public string $passportReference,
        public ?string $occupation,
        /** Employers browsing the pool see this graduate (they chose to be found). */
        public bool $visibleToEmployers,
        public bool $hasSkillShow,
        public bool $attested,
    ) {}

    public static function fromProfile(WorkerProfile $profile, bool $visibleToEmployers): self
    {
        return new self(
            profileId: $profile->id,
            name: (string) $profile->user?->name,
            passportReference: (string) $profile->reference,
            occupation: $profile->occupation?->translatedName(),
            visibleToEmployers: $visibleToEmployers,
            hasSkillShow: $profile->skillShows->contains(fn (SkillShow $skillShow) => $skillShow->isPublished()),
            attested: $profile->evidenceItems->contains(fn (EvidenceItem $item) => $item->type === EvidenceType::Training && $item->status->getValue() === 'issuer_verified'),
        );
    }
}
