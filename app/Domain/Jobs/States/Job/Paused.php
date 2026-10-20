<?php

namespace App\Domain\Jobs\States\Job;

class Paused extends JobState
{
    /** @var string */
    public static $name = 'paused';

    public function label(): string
    {
        return 'Paused';
    }

    public function tone(): string
    {
        return 'neutral';
    }
}
