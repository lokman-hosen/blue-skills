<?php

namespace App\Domain\Applications\States\Application;

class Reviewed extends ApplicationState
{
    /** @var string */
    public static $name = 'reviewed';

    public function label(): string
    {
        return 'Profile reviewed';
    }

    public function tone(): string
    {
        return 'brand';
    }
}
