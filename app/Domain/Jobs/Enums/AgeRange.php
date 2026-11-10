<?php

namespace App\Domain\Jobs\Enums;

/**
 * Age range the employer asks for. Not specified is stored as null. Shown on the Truth Card only,
 * never used for matching (D-117).
 */
enum AgeRange: string
{
    case From18To25 = '18_25';
    case From18To35 = '18_35';
    case From18To45 = '18_45';
    case From18To50 = '18_50';
    case From18To60 = '18_60';
    case Other = 'other';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::From18To25 => '18–25',
            self::From18To35 => '18–35',
            self::From18To45 => '18–45',
            self::From18To50 => '18–50',
            self::From18To60 => '18–60',
            self::Other => 'Other',
        };
    }
}
