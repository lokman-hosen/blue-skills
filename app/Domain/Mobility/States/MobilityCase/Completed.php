<?php

namespace App\Domain\Mobility\States\MobilityCase;

class Completed extends MobilityCaseState
{
    /** @var string */
    public static $name = 'completed';

    public function label(): string
    {
        return 'Completed';
    }

    public function tone(): string
    {
        return 'ok';
    }
}
