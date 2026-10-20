<?php

namespace App\Domain\Jobs\States\Job;

class Suspended extends JobState
{
    /** @var string */
    public static $name = 'suspended';

    public function label(): string
    {
        return 'Suspended';
    }

    public function tone(): string
    {
        return 'bad';
    }
}
