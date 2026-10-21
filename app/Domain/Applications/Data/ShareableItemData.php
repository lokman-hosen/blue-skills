<?php

namespace App\Domain\Applications\Data;

use App\Domain\Applications\Enums\ApplicationConsentItem;
use Spatie\LaravelData\Data;

/**
 * One line on the "Share with employer?" screen (deck slide 12).
 */
class ShareableItemData extends Data
{
    /**
     * @param  array<string, string|int>  $params  Values for the detail text.
     */
    public function __construct(
        public ApplicationConsentItem $item,
        /** The Skill Passport is part of every application. */
        public bool $required,
        /** Passport scan and phone number: off by default, flagged as sensitive. */
        public bool $sensitive,
        /** False when there is nothing to share yet (no approved SkillShow, no scan uploaded…). */
        public bool $available,
        public bool $sharedByDefault,
        /** Translation key under `applications.item_detail.` */
        public string $detail,
        public array $params = [],
    ) {}
}
