<?php

namespace App\Domain\Applications\States\Application;

class Qualified extends ApplicationState
{
    /** @var string */
    public static $name = 'qualified';

    public function label(): string
    {
        return 'Qualified';
    }

    public function tone(): string
    {
        return 'brand';
    }
}
