<?php

namespace App\Domain\Verification\States\VerificationRequest;

class Open extends VerificationRequestState
{
    /** @var string */
    public static $name = 'open';

    public function label(): string
    {
        return 'Open';
    }

    public function tone(): string
    {
        return 'brand';
    }
}
