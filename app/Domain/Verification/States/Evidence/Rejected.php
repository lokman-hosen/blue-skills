<?php

namespace App\Domain\Verification\States\Evidence;

class Rejected extends EvidenceState
{
    /** @var string */
    public static $name = 'rejected';

    public function label(): string
    {
        return 'Rejected';
    }

    public function tone(): string
    {
        return 'bad';
    }
}
