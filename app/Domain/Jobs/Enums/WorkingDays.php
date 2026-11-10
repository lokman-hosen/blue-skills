<?php

namespace App\Domain\Jobs\Enums;

/**
 * Working days. "Other" carries the poster's own words in `working_days_other`.
 */
enum WorkingDays: string
{
    case Five = 'days_5';
    case FiveToSix = 'days_5_6';
    case Six = 'days_6';
    case Rotating = 'rotating';
    case Other = 'other';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Five => '5 days/week',
            self::FiveToSix => '5–6 days/week',
            self::Six => '6 days/week',
            self::Rotating => 'Rotating roster',
            self::Other => 'Other',
        };
    }
}
