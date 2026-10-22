<?php

namespace App\Domain\Billing\States\Subscription;

class Active extends SubscriptionState
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
