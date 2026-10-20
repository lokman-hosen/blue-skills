<?php

namespace App\Domain\Jobs\States\Job;

class MoreInformationRequested extends JobState
{
    /** @var string */
    public static $name = 'more_information_requested';

    public function label(): string
    {
        return 'More information requested';
    }

    public function tone(): string
    {
        return 'warn';
    }
}
