<?php

namespace App\Domain\Jobs\States\Job;

class Draft extends JobState
{
    /** @var string */
    public static $name = 'draft';

    public function label(): string
    {
        return 'Draft';
    }

    public function tone(): string
    {
        return 'neutral';
    }
}
