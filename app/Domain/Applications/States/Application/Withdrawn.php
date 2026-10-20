<?php

namespace App\Domain\Applications\States\Application;

class Withdrawn extends ApplicationState
{
    /** @var string */
    public static $name = 'withdrawn';

    public function label(): string
    {
        return 'Withdrawn';
    }

    public function tone(): string
    {
        return 'neutral';
    }
}
