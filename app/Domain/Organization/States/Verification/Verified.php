<?php

namespace App\Domain\Organization\States\Verification;

class Verified extends OrganizationVerificationState
{
    /** @var string */
    public static $name = 'verified';

    public function label(): string
    {
        return 'Verified';
    }

    public function tone(): string
    {
        return 'ok';
    }
}
