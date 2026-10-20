<?php

namespace App\Domain\Verification\States\VerificationRequest;

class NeedsInfo extends VerificationRequestState
{
    /** @var string */
    public static $name = 'needs_info';

    public function label(): string
    {
        return 'Needs information';
    }

    public function tone(): string
    {
        return 'warn';
    }
}
