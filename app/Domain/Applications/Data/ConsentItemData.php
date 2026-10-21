<?php

namespace App\Domain\Applications\Data;

use App\Domain\Applications\Enums\ApplicationConsentItem;
use App\Domain\Applications\Models\ApplicationConsent;
use Spatie\LaravelData\Data;

class ConsentItemData extends Data
{
    public function __construct(
        public ApplicationConsentItem $item,
        public bool $active,
        public ?string $grantedAt,
        public ?string $revokedAt,
        /** The Skill Passport cannot be withdrawn from an application. */
        public bool $revocable,
    ) {}

    public static function fromConsent(ApplicationConsent $consent): self
    {
        return new self(
            item: $consent->item,
            active: $consent->isActive(),
            grantedAt: $consent->granted_at?->toIso8601String(),
            revokedAt: $consent->revoked_at?->toIso8601String(),
            revocable: $consent->isActive() && $consent->item !== ApplicationConsentItem::SkillPassport,
        );
    }
}
