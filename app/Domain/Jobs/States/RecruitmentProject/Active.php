<?php

namespace App\Domain\Jobs\States\RecruitmentProject;

class Active extends RecruitmentProjectState
{
    /** @var string */
    public static $name = 'active';

    public function label(): string
    {
        return 'Active';
    }

    public function tone(): string
    {
        return 'brand';
    }
}
