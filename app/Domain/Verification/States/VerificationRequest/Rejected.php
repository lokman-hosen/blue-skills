<?php

namespace App\Domain\Verification\States\VerificationRequest;

class Rejected extends VerificationRequestState
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
