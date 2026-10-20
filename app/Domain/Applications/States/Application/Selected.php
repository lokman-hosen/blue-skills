<?php

namespace App\Domain\Applications\States\Application;

class Selected extends ApplicationState
{
    /** @var string */
    public static $name = 'selected';

    public function label(): string
    {
        return 'Selected';
    }

    public function tone(): string
    {
        return 'ok';
    }
}
