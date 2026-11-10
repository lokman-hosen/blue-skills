<?php

namespace App\Domain\Jobs\Enums;

/**
 * Typical working hours. "Other" carries the poster's own words in `working_hours_other`.
 */
enum WorkingHours: string
{
    case Day6To8 = 'day_6_8';
    case Day8To10 = 'day_8_10';
    case Day10To12 = 'day_10_12';
    case Month160To200 = 'month_160_200';
    case Month200To270 = 'month_200_270';
    case Variable = 'variable';
    case Other = 'other';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Day6To8 => '6–8 hours/day',
            self::Day8To10 => '8–10 hours/day',
            self::Day10To12 => '10–12 hours/day',
            self::Month160To200 => '160–200 hours/month',
            self::Month200To270 => '200–270 hours/month',
            self::Variable => 'Variable',
            self::Other => 'Other',
        };
    }
}
