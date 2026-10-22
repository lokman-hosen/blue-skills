<?php

namespace App\Domain\Crm\Models;

use App\Domain\Crm\Enums\ContactChannel;
use App\Domain\Crm\Enums\ContactOutcome;
use App\Domain\Identity\Models\User;
use Database\Factories\LeadContactAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * One call, message or visit to a lead and how it went. Never edited.
 *
 * @property Carbon $attempted_at
 */
#[Fillable(['lead_id', 'staff_id', 'channel', 'outcome', 'note', 'attempted_at'])]
#[UseFactory(LeadContactAttemptFactory::class)]
class LeadContactAttempt extends Model
{
    /** @use HasFactory<LeadContactAttemptFactory> */
    use HasFactory, HasUlids;

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Contact attempts are immutable.');
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => ContactChannel::class,
            'outcome' => ContactOutcome::class,
            'attempted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}
