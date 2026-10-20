<?php

namespace App\Domain\Jobs\States\Job;

class Rejected extends JobState
{
    /** @var string */
    public static $name = 'rejected';

    public function label(): string
    {
        return 'Rejected';
    }

    public function tone(): string
    {
        return 'bad';
    }
}
