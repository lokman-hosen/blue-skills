<?php

namespace App\Domain\Worker\Enums;

/**
 * Why a moderator did not publish a SkillShow. Shown to the worker with how to fix it.
 */
enum SkillShowRejectionReason: string
{
    case NotADemonstration = 'not_a_demonstration';
    case UnsafeWork = 'unsafe_work';
    case PersonalInformation = 'personal_information';
    case PoorQuality = 'poor_quality';
    case Inappropriate = 'inappropriate';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::NotADemonstration => 'Does not show the work',
            self::UnsafeWork => 'Unsafe working practice',
            self::PersonalInformation => 'Shows personal or contact details',
            self::PoorQuality => 'Too dark, blurred or noisy',
            self::Inappropriate => 'Inappropriate content',
        };
    }
}
