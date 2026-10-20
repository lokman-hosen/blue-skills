<?php

namespace App\Domain\Organization\States\Verification;

class Suspended extends OrganizationVerificationState
{
    /** @var string */
    public static $name = 'suspended';

    public function label(): string
    {
        return 'Suspended';
    }

    public function tone(): string
    {
        return 'bad';
    }
}
