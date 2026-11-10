<?php

namespace App\Domain\Jobs\Enums;

/**
 * Full-time, part-time, seasonal or fixed-term work (job posting prototype, step 1).
 */
enum EmploymentType: string
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Seasonal = 'seasonal';
    case FixedTerm = 'fixed_term';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::FullTime => 'Full-time',
            self::PartTime => 'Part-time',
            self::Seasonal => 'Seasonal',
            self::FixedTerm => 'Fixed-term',
        };
    }
}
