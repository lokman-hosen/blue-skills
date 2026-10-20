<?php

namespace App\Domain\Trust\States\Report;

class Actioned extends ReportState
{
    /** @var string */
    public static $name = 'actioned';

    public function label(): string
    {
        return 'Actioned';
    }

    public function tone(): string
    {
        return 'ok';
    }
}
