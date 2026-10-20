<?php

namespace App\Domain\Jobs\States\Job;

class Closed extends JobState
{
    /** @var string */
    public static $name = 'closed';

    public function label(): string
    {
        return 'Closed';
    }

    public function tone(): string
    {
        return 'neutral';
    }
}
