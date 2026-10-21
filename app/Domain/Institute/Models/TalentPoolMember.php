<?php

namespace App\Domain\Institute\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * A graduate in a talent pool.
 */
class TalentPoolMember extends Pivot
{
    use HasUlids;

    public $incrementing = false;

    protected $table = 'talent_pool_members';
}
