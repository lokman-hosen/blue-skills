<?php

namespace App\Domain\Jobs\States\Job;

class Archived extends JobState
{
    /** @var string */
    public static $name = 'archived';

    public function label(): string
    {
        return 'Archived';
    }

    public function tone(): string
    {
        return 'neutral';
    }
}
