<?php

namespace App\Domain\Mobility\States\MobilityCase;

class Cancelled extends MobilityCaseState
{
    /** @var string */
    public static $name = 'cancelled';

    public function label(): string
    {
        return 'Cancelled';
    }

    public function tone(): string
    {
        return 'neutral';
    }
}
