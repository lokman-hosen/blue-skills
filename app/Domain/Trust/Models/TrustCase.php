<?php

namespace App\Domain\Trust\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Trust\Enums\CasePriority;
use App\Domain\Trust\Enums\ReportCategory;
use App\Domain\Trust\States\TrustCase\Closed;
use App\Domain\Trust\States\TrustCase\TrustCaseState;
use App\Support\Concerns\LogsStatusChanges;
use Database\Factories\TrustCaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Spatie\ModelStates\HasStates;

/**
 * A trust case about an organization, job or worker (spec §10): linked reports, evidence
 * notes, suspensions and their appeals, with a reason code on every decision.
 *
 * @property TrustCaseState $status
 * @property Carbon|null $closed_at
 */
#[Fillable([
    'reference', 'subject_type', 'subject_id', 'category', 'priority', 'status', 'summary', 'assigned_to', 'opened_by',
    'outcome_code', 'closed_by', 'closed_at',
])]
#[UseFactory(TrustCaseFactory::class)]
class TrustCase extends Model
{
    /** @use HasFactory<TrustCaseFactory> */
    use HasFactory, HasStates, HasUlids, LogsStatusChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ReportCategory::class,
            'priority' => CasePriority::class,
            'status' => TrustCaseState::class,
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    protected function loggedStatusAttributes(): array
    {
        return ['status', 'assigned_to', 'priority'];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    /**
     * @return HasMany<Report, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class)->latest();
    }

    /**
     * @return HasMany<TrustCaseNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(TrustCaseNote::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * @return HasMany<TrustAction, $this>
     */
    public function actions(): HasMany
    {
        return $this->hasMany(TrustAction::class)->orderBy('created_at')->orderBy('id');
    }

    public function isClosed(): bool
    {
        return $this->status->equals(Closed::class);
    }

    /**
     * TC-2026-0001: year and a running number.
     */
    public static function newReference(): string
    {
        $prefix = 'TC-'.now()->year.'-';
        $last = self::query()->where('reference', 'like', $prefix.'%')->max('reference');
        $next = $last !== null ? ((int) substr((string) $last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
