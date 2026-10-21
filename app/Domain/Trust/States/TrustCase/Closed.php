<?php

namespace App\Domain\Trust\States\TrustCase;

class Closed extends TrustCaseState
{
    /** @var string */
    public static $name = 'closed';

    public function label(): string
    {
        return 'Closed';
    }

    public function tone(): string
    {
        return 'neutral';
    }
}
