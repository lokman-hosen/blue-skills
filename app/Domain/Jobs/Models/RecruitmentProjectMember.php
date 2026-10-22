<?php

namespace App\Domain\Jobs\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * A team member assigned to a recruitment project.
 */
class RecruitmentProjectMember extends Pivot
{
    use HasUlids;

    public $incrementing = false;

    protected $table = 'recruitment_project_members';
}
