<?php

namespace App\Domain\Crm\States\Campaign;

class Active extends CampaignState
{
    /** @var string */
    public static $name = 'active';

    public function label(): string
    {
        return 'Active';
    }

    public function tone(): string
    {
        return 'ok';
    }
}
