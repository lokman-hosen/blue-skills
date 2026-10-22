<?php

namespace App\Domain\Crm\States\Campaign;

class Paused extends CampaignState
{
    /** @var string */
    public static $name = 'paused';

    public function label(): string
    {
        return 'Paused';
    }

    public function tone(): string
    {
        return 'warn';
    }
}
