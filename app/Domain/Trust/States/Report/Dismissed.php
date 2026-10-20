<?php

namespace App\Domain\Trust\States\Report;

class Dismissed extends ReportState
{
    /** @var string */
    public static $name = 'dismissed';

    public function label(): string
    {
        return 'Dismissed';
    }

    public function tone(): string
    {
        return 'neutral';
    }
}
