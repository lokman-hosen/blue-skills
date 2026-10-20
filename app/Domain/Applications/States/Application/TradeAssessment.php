<?php

namespace App\Domain\Applications\States\Application;

class TradeAssessment extends ApplicationState
{
    /** @var string */
    public static $name = 'trade_assessment';

    public function label(): string
    {
        return 'Trade assessment';
    }

    public function tone(): string
    {
        return 'stamp';
    }
}
