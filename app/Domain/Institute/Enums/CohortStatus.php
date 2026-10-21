<?php

namespace App\Domain\Institute\Enums;

/**
 * An open cohort takes graduates through its invite link; a closed one doesn't.
 */
enum CohortStatus: string
{
    case Open = 'open';
    case Closed = 'closed';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open for graduates',
            self::Closed => 'Closed',
        };
    }

    public function tone(): string
    {
        return $this === self::Open ? 'ok' : 'neutral';
    }
}
