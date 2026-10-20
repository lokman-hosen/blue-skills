<?php

namespace App\Domain\Trust\States\Report;

class Investigating extends ReportState
{
    /** @var string */
    public static $name = 'investigating';

    public function label(): string
    {
        return 'Investigating';
    }

    public function tone(): string
    {
        return 'warn';
    }
}
