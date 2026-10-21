<?php

namespace App\Domain\Institute\Enums;

/**
 * Attestations and assessment records stay issued until the institute withdraws them with a
 * reason (D-082).
 */
enum IssuedRecordStatus: string
{
    case Issued = 'issued';
    case Withdrawn = 'withdrawn';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return $this === self::Issued ? 'Issued' : 'Withdrawn';
    }

    public function tone(): string
    {
        return $this === self::Issued ? 'ok' : 'bad';
    }
}
