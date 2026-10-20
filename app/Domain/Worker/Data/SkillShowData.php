<?php

namespace App\Domain\Worker\Data;

use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Worker\Enums\ModerationStatus;
use App\Domain\Worker\Enums\ProfileVisibility;
use App\Domain\Worker\Models\SkillShow;
use App\Domain\Worker\Support\MediaUrls;
use Spatie\LaravelData\Data;

/**
 * A SkillShow as its worker or a moderator sees it. Video and poster links are signed and
 * expire (CLAUDE.md: sensitive files).
 */
class SkillShowData extends Data
{
    /**
     * @param  list<string>  $skills  Skill names.
     * @param  list<string>  $skillSlugs
     */
    public function __construct(
        public string $id,
        public ModerationStatus $status,
        public ?int $durationMs,
        public ProfileVisibility $visibility,
        public array $skills,
        public array $skillSlugs,
        public ?string $reasonCode,
        public ?string $videoUrl,
        public ?string $posterUrl,
        public ?string $createdAt,
    ) {}

    public static function fromSkillShow(SkillShow $skillShow): self
    {
        $skillShow->loadMissing('skills');

        return new self(
            id: $skillShow->id,
            status: $skillShow->moderation_status,
            durationMs: $skillShow->duration_ms,
            visibility: $skillShow->visibility,
            skills: $skillShow->skills->map(fn (Skill $skill) => $skill->translatedName())->values()->all(),
            skillSlugs: $skillShow->skills->pluck('slug')->values()->all(),
            reasonCode: $skillShow->reason_code,
            videoUrl: MediaUrls::signed($skillShow->getFirstMedia(SkillShow::VIDEO_COLLECTION)),
            posterUrl: MediaUrls::signed($skillShow->getFirstMedia(SkillShow::POSTER_COLLECTION)),
            createdAt: $skillShow->created_at?->toIso8601String(),
        );
    }
}
