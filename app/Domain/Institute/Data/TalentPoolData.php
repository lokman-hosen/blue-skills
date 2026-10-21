<?php

namespace App\Domain\Institute\Data;

use App\Domain\Institute\Models\TalentPool;
use Spatie\LaravelData\Data;

/**
 * A pool in its institute's list, or, published, in the list employers and agencies browse.
 */
class TalentPoolData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public ?string $occupationId,
        public ?string $occupation,
        public string $institute,
        public bool $instituteVerified,
        public ?string $publishedAt,
        public int $members,
        /** Members who chose to be visible to employers: what browsing organizations see. */
        public int $visibleMembers,
    ) {}

    public static function fromPool(TalentPool $pool): self
    {
        $pool->loadMissing(['organization', 'occupation']);

        return new self(
            id: $pool->id,
            name: $pool->name,
            description: $pool->description,
            occupationId: $pool->occupation_id,
            occupation: $pool->occupation?->translatedName(),
            institute: (string) $pool->organization->display_name,
            instituteVerified: $pool->organization->isVerified(),
            publishedAt: $pool->published_at?->toIso8601String(),
            members: $pool->workers()->count(),
            visibleMembers: $pool->workers()->discoverable()->count(),
        );
    }
}
