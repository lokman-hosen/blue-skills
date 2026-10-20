<?php

namespace App\Domain\Applications\States\Application;

class Rejected extends ApplicationState
{
    /** @var string */
    public static $name = 'rejected';

    public function label(): string
    {
        return 'Not selected';
    }

    public function tone(): string
    {
        return 'bad';
    }
}
