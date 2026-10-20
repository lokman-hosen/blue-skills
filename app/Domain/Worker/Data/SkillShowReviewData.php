<?php

namespace App\Domain\Worker\Data;

use App\Domain\Worker\Models\SkillShow;
use Spatie\LaravelData\Data;

/**
 * A SkillShow in the moderation queue. Only what a moderator needs: no phone or documents.
 */
class SkillShowReviewData extends Data
{
    public function __construct(
        public SkillShowData $skillShow,
        public string $workerName,
        public string $reference,
        public ?string $occupation,
    ) {}

    public static function fromSkillShow(SkillShow $skillShow): self
    {
        $profile = $skillShow->workerProfile;

        return new self(
            skillShow: SkillShowData::fromSkillShow($skillShow),
            workerName: $profile->user->name,
            reference: $profile->reference,
            occupation: $profile->occupation?->translatedName(),
        );
    }
}
