<?php

namespace App\Domain\Jobs\States\Job;

class UnderReview extends JobState
{
    /** @var string */
    public static $name = 'under_review';

    public function label(): string
    {
        return 'Under review';
    }

    public function tone(): string
    {
        return 'warn';
    }
}
