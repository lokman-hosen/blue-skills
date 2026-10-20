<?php

namespace App\Domain\Verification\States\VerificationRequest;

class Approved extends VerificationRequestState
{
    /** @var string */
    public static $name = 'approved';

    public function label(): string
    {
        return 'Approved';
    }

    public function tone(): string
    {
        return 'ok';
    }
}
