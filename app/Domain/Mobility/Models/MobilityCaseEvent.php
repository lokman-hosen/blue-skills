<?php

namespace App\Domain\Mobility\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Mobility\Enums\CaseEventAction;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One step in a case's history. Never edited.
 */
#[Fillable(['mobility_case_id', 'stage_key', 'action', 'actor_user_id', 'note'])]
class MobilityCaseEvent extends Model
{
    use HasUlids;

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Mobility case events are immutable.');
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['action' => CaseEventAction::class];
    }

    /**
     * @return BelongsTo<MobilityCase, $this>
     */
    public function mobilityCase(): BelongsTo
    {
        return $this->belongsTo(MobilityCase::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
