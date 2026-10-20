<?php

namespace App\Domain\Mobility\States\MobilityCase;

class Open extends MobilityCaseState
{
    /** @var string */
    public static $name = 'open';

    public function label(): string
    {
        return 'Open';
    }

    public function tone(): string
    {
        return 'brand';
    }
}
