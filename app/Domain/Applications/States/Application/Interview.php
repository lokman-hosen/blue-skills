<?php

namespace App\Domain\Applications\States\Application;

class Interview extends ApplicationState
{
    /** @var string */
    public static $name = 'interview';

    public function label(): string
    {
        return 'Interview';
    }

    public function tone(): string
    {
        return 'stamp';
    }
}
