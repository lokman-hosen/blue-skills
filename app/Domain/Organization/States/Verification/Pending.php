<?php

namespace App\Domain\Organization\States\Verification;

class Pending extends OrganizationVerificationState
{
    /** @var string */
    public static $name = 'pending';

    public function label(): string
    {
        return 'Verification pending';
    }

    public function tone(): string
    {
        return 'warn';
    }
}
