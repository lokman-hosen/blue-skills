<?php

namespace App\Domain\Verification\States\Evidence;

class IssuerVerified extends EvidenceState
{
    /** @var string */
    public static $name = 'issuer_verified';

    public function label(): string
    {
        return 'Issuer verified';
    }

    public function tone(): string
    {
        return 'ok';
    }
}
