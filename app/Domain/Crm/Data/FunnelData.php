<?php

namespace App\Domain\Crm\Data;

use Spatie\LaravelData\Data;

/**
 * Campaign analytics (spec §9): visitors, then what became of the people who arrived in the
 * period: leads, registrations, completed profiles, qualified, applied, selected and deployed.
 * Later steps count the same group of people today, so the numbers can only grow over time.
 */
class FunnelData extends Data
{
    public function __construct(
        public int $visitors = 0,
        public int $leads = 0,
        public int $registrations = 0,
        public int $profilesComplete = 0,
        public int $qualified = 0,
        public int $applied = 0,
        public int $selected = 0,
        public int $deployed = 0,
    ) {}

    public function plus(self $other): self
    {
        return new self(
            $this->visitors + $other->visitors,
            $this->leads + $other->leads,
            $this->registrations + $other->registrations,
            $this->profilesComplete + $other->profilesComplete,
            $this->qualified + $other->qualified,
            $this->applied + $other->applied,
            $this->selected + $other->selected,
            $this->deployed + $other->deployed,
        );
    }
}
