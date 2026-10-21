<?php

namespace App\Domain\Trust\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Trust\Enums\ReportCategory;
use App\Domain\Trust\Enums\ReportOutcome;
use App\Domain\Trust\Enums\TrustReason;
use App\Domain\Trust\States\Report\ReportState;
use App\Support\Concerns\LogsStatusChanges;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Spatie\ModelStates\HasStates;

/**
 * Something a user reported (spec §10). Staff dismiss it with a reason or handle it on a trust
 * case; the reporter is told only the outcome (D-088).
 *
 * @property ReportState $status
 * @property Carbon|null $decided_at
 */
#[Fillable(['reporter_user_id', 'subject_type', 'subject_id', 'category', 'description', 'status', 'trust_case_id', 'outcome', 'reason_code', 'decided_by', 'decided_at'])]
#[UseFactory(ReportFactory::class)]
class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory, HasStates, HasUlids, LogsStatusChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ReportCategory::class,
            'status' => ReportState::class,
            'outcome' => ReportOutcome::class,
            'reason_code' => TrustReason::class,
            'decided_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<TrustCase, $this>
     */
    public function trustCase(): BelongsTo
    {
        return $this->belongsTo(TrustCase::class);
    }
}
