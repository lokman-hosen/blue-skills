<?php

namespace App\Domain\Organization\States\Verification;

class Rejected extends OrganizationVerificationState
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
