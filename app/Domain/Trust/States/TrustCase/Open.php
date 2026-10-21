<?php

namespace App\Domain\Trust\States\TrustCase;

class Open extends TrustCaseState
{
    /** @var string */
    public static $name = 'open';

    public function label(): string
    {
        return 'Open';
    }

    public function tone(): string
    {
        return 'warn';
    }
}
