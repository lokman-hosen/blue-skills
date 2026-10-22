<?php

namespace App\Domain\Jobs\States\RecruitmentProject;

class Completed extends RecruitmentProjectState
{
    /** @var string */
    public static $name = 'completed';

    public function label(): string
    {
        return 'Completed';
    }

    public function tone(): string
    {
        return 'ok';
    }
}
