<?php

namespace App\Domain\Jobs\Enums;

/**
 * Contract length. "Other" carries the poster's own words in `contract_other`. The duration also
 * sets `contract_months` (its lower bound) for country disclosure rules and older readers.
 */
enum ContractDuration: string
{
    case Months3 = '3_months';
    case Months6 = '6_months';
    case Months9 = '9_months';
    case Months12 = '12_months';
    case Months12To24 = '12_24_months';
    case Months12To36 = '12_36_months';
    case Years2 = '2_years';
    case Permanent = 'permanent';
    case Other = 'other';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Months3 => '3 months',
            self::Months6 => '6 months',
            self::Months9 => '9 months',
            self::Months12 => '12 months',
            self::Months12To24 => '12–24 months',
            self::Months12To36 => '12–36 months',
            self::Years2 => '2 years',
            self::Permanent => 'Permanent',
            self::Other => 'Other',
        };
    }

    public function months(): ?int
    {
        return match ($this) {
            self::Months3 => 3,
            self::Months6 => 6,
            self::Months9 => 9,
            self::Months12, self::Months12To24, self::Months12To36 => 12,
            self::Years2 => 24,
            self::Permanent, self::Other => null,
        };
    }

    /**
     * The duration for a length stored before durations existed; null when none matches exactly.
     */
    public static function fromMonths(int $months): ?self
    {
        return match ($months) {
            3 => self::Months3,
            6 => self::Months6,
            9 => self::Months9,
            12 => self::Months12,
            24 => self::Years2,
            default => null,
        };
    }
}
