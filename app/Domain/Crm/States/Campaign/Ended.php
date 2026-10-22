<?php

namespace App\Domain\Crm\States\Campaign;

class Ended extends CampaignState
{
    /** @var string */
    public static $name = 'ended';

    public function label(): string
    {
        return 'Ended';
    }

    public function tone(): string
    {
        return 'neutral';
    }
}
