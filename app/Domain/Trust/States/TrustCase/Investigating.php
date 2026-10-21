<?php

namespace App\Domain\Trust\States\TrustCase;

class Investigating extends TrustCaseState
{
    /** @var string */
    public static $name = 'investigating';

    public function label(): string
    {
        return 'Investigating';
    }

    public function tone(): string
    {
        return 'brand';
    }
}
