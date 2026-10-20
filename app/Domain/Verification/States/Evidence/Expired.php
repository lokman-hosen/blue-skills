<?php

namespace App\Domain\Verification\States\Evidence;

class Expired extends EvidenceState
{
    /** @var string */
    public static $name = 'expired';

    public function label(): string
    {
        return 'Expired';
    }

    public function tone(): string
    {
        return 'bad';
    }
}
