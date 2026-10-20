<?php

namespace App\Domain\Organization\States\Verification;

class Unverified extends OrganizationVerificationState
{
    /** @var string */
    public static $name = 'unverified';

    public function label(): string
    {
        return 'Unverified';
    }

    public function tone(): string
    {
        return 'neutral';
    }
}
