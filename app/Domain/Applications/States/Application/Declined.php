<?php

namespace App\Domain\Applications\States\Application;

class Declined extends ApplicationState
{
    /** @var string */
    public static $name = 'declined';

    public function label(): string
    {
        return 'Declined';
    }

    public function tone(): string
    {
        return 'neutral';
    }
}
