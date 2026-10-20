<?php

namespace App\Domain\Applications\States\Application;

class Shortlisted extends ApplicationState
{
    /** @var string */
    public static $name = 'shortlisted';

    public function label(): string
    {
        return 'Shortlisted';
    }

    public function tone(): string
    {
        return 'stamp';
    }
}
