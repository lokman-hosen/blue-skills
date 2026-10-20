<?php

namespace App\Domain\Applications\States\Application;

class Accepted extends ApplicationState
{
    /** @var string */
    public static $name = 'accepted';

    public function label(): string
    {
        return 'Accepted';
    }

    public function tone(): string
    {
        return 'ok';
    }
}
