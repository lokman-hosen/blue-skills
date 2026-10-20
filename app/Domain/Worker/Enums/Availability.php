<?php

namespace App\Domain\Worker\Enums;

enum Availability: string
{
    case Immediately = 'immediately';
    case Within30Days = 'within_30_days';
    case Within60Days = 'within_60_days';
    case Within90Days = 'within_90_days';
    case NotAvailable = 'not_available';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Immediately => 'Immediately',
            self::Within30Days => 'Within 30 days',
            self::Within60Days => 'Within 60 days',
            self::Within90Days => 'Within 90 days',
            self::NotAvailable => 'Not available',
        };
    }
}
