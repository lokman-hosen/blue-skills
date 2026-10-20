<?php

namespace App\Domain\Trust\States\Report;

class Open extends ReportState
{
    /** @var string */
    public static $name = 'open';

    public function label(): string
    {
        return 'Open';
    }

    public function tone(): string
    {
        return 'warn';
    }
}
