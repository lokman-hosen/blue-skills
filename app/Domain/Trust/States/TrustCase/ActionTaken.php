<?php

namespace App\Domain\Trust\States\TrustCase;

class ActionTaken extends TrustCaseState
{
    /** @var string */
    public static $name = 'action_taken';

    public function label(): string
    {
        return 'Action taken';
    }

    public function tone(): string
    {
        return 'bad';
    }
}
