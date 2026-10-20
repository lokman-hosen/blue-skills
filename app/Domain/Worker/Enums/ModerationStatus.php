<?php

namespace App\Domain\Worker\Enums;

/**
 * SkillShow moderation (D-014: a categorical enum, not a model-state). `processing` and
 * `failed` cover the server-side video check that runs before a moderator sees it.
 */
enum ModerationStatus: string
{
    case Processing = 'processing';
    case Failed = 'failed';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Processing => 'Processing',
            self::Failed => 'Failed',
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }
}
