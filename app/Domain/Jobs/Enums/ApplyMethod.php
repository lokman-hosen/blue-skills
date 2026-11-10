<?php

namespace App\Domain\Jobs\Enums;

/**
 * How workers apply. BLUESKILLS always takes applications (snapshot and consent, rules 6–7); "Both"
 * also shows the employer's own page as a secondary link (D-117).
 */
enum ApplyMethod: string
{
    case Blueskills = 'blueskills';
    case Both = 'both';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Blueskills => 'BLUESKILLS',
            self::Both => 'BLUESKILLS and external website',
        };
    }
}
