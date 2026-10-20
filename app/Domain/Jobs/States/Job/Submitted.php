<?php

namespace App\Domain\Jobs\States\Job;

class Submitted extends JobState
{
    /** @var string */
    public static $name = 'submitted';

    public function label(): string
    {
        return 'Submitted';
    }

    public function tone(): string
    {
        return 'brand';
    }
}
