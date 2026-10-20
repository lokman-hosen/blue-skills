<?php

namespace App\Domain\Jobs\States\Job;

class Published extends JobState
{
    /** @var string */
    public static $name = 'published';

    public function label(): string
    {
        return 'Published';
    }

    public function tone(): string
    {
        return 'ok';
    }
}
