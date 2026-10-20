<?php

namespace App\Domain\Applications\States\Application;

class Applied extends ApplicationState
{
    /** @var string */
    public static $name = 'applied';

    public function label(): string
    {
        return 'Applied';
    }

    public function tone(): string
    {
        return 'brand';
    }
}
