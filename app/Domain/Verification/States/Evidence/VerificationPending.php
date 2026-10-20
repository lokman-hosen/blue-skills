<?php

namespace App\Domain\Verification\States\Evidence;

class VerificationPending extends EvidenceState
{
    /** @var string */
    public static $name = 'verification_pending';

    public function label(): string
    {
        return 'Verification pending';
    }

    public function tone(): string
    {
        return 'warn';
    }
}
