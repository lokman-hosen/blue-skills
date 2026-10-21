<?php

namespace App\Domain\Applications\Models;

use App\Domain\Applications\Enums\InterviewMode;
use App\Domain\Applications\Enums\InterviewOutcome;
use App\Domain\Identity\Models\User;
use Database\Factories\InterviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An interview booked for one application. The worker sees the time, mode, place and
 * instructions; the outcome is recorded by the organization.
 *
 * @property Carbon $scheduled_at
 */
#[Fillable([
    'application_id', 'scheduled_at', 'duration_minutes', 'mode', 'location', 'instructions', 'outcome',
    'outcome_note', 'scheduled_by',
])]
#[UseFactory(InterviewFactory::class)]
class Interview extends Model
{
    /** @use HasFactory<InterviewFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
            'mode' => InterviewMode::class,
            'outcome' => InterviewOutcome::class,
        ];
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
    public function scheduler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    public function isUpcoming(): bool
    {
        return $this->outcome === null && $this->scheduled_at->isFuture();
    }
}
