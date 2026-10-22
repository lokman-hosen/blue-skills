<?php

namespace App\Domain\Billing\States\Subscription;

class Ended extends SubscriptionState
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
