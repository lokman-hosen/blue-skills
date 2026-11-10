<?php

namespace App\Domain\Jobs\Enums;

/**
 * Whether the quoted pay is before or after deductions. Unknown is stored as null ("Not disclosed").
 */
enum SalaryBasis: string
{
    case Net = 'net';
    case Gross = 'gross';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Net => 'Net',
            self::Gross => 'Gross',
        };
    }
}
