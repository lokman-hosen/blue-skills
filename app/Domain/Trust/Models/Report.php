<?php

namespace App\Domain\Trust\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Trust\Enums\ReportCategory;
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
use Spatie\ModelStates\HasStates;

/**
 * @property ReportState $status
 */
#[Fillable(['reporter_user_id', 'subject_type', 'subject_id', 'category', 'description', 'status'])]
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
}
