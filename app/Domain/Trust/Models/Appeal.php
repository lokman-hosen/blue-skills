<?php

namespace App\Domain\Trust\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Trust\Enums\AppealStatus;
use App\Support\Concerns\LogsStatusChanges;
use Database\Factories\AppealFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An appeal against a suspension (spec §10). Decided by a trust officer other than the one who
 * suspended (D-088).
 *
 * @property Carbon|null $decided_at
 */
#[Fillable(['trust_action_id', 'appellant_user_id', 'statement', 'status', 'decided_by', 'decision_note', 'decided_at'])]
#[UseFactory(AppealFactory::class)]
class Appeal extends Model
{
    /** @use HasFactory<AppealFactory> */
    use HasFactory, HasUlids, LogsStatusChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AppealStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TrustAction, $this>
     */
    public function suspension(): BelongsTo
    {
        return $this->belongsTo(TrustAction::class, 'trust_action_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function appellant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'appellant_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
