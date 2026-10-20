<?php

namespace App\Domain\Applications\States\Application;

class Offer extends ApplicationState
{
    /** @var string */
    public static $name = 'offer';

    public function label(): string
    {
        return 'Offer';
    }

    public function tone(): string
    {
        return 'ok';
    }
}
