<?php

namespace App\Domain\Applications\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Worker\Models\WorkerProfile;
use Database\Factories\ShortlistEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['shortlist_id', 'worker_profile_id', 'note', 'added_by'])]
#[UseFactory(ShortlistEntryFactory::class)]
class ShortlistEntry extends Model
{
    /** @use HasFactory<ShortlistEntryFactory> */
    use HasFactory, HasUlids;

    /**
     * @return BelongsTo<Shortlist, $this>
     */
    public function shortlist(): BelongsTo
    {
        return $this->belongsTo(Shortlist::class);
    }

    /**
     * @return BelongsTo<WorkerProfile, $this>
     */
    public function workerProfile(): BelongsTo
    {
        return $this->belongsTo(WorkerProfile::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function adder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
