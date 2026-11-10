<?php

namespace App\Domain\Jobs\Enums;

/**
 * Shift pattern. "Other" carries the poster's own words in `shift_pattern_other`.
 */
enum ShiftPattern: string
{
    case Day = 'day';
    case Night = 'night';
    case Rotating = 'rotating';
    case Flexible = 'flexible';
    case Other = 'other';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Day => 'Day',
            self::Night => 'Night',
            self::Rotating => 'Rotating',
            self::Flexible => 'Flexible',
            self::Other => 'Other',
        };
    }
}
