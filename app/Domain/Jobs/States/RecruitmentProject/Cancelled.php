<?php

namespace App\Domain\Jobs\States\RecruitmentProject;

class Cancelled extends RecruitmentProjectState
{
    /** @var string */
    public static $name = 'cancelled';

    public function label(): string
    {
        return 'Cancelled';
    }

    public function tone(): string
    {
        return 'neutral';
    }
}
