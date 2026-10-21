<?php

namespace App\Domain\Applications\Models;

use App\Domain\Identity\Models\User;
use Database\Factories\ApplicationStageEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One step in an application's history: who moved it, from where to where, and why. The worker
 * sees the same timeline (spec §6). Events are never edited.
 */
#[Fillable(['application_id', 'from_status', 'to_status', 'actor_user_id', 'reason_code', 'note'])]
#[UseFactory(ApplicationStageEventFactory::class)]
class ApplicationStageEvent extends Model
{
    /** @use HasFactory<ApplicationStageEventFactory> */
    use HasFactory, HasUlids;

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Application stage events are immutable.');
        });
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
