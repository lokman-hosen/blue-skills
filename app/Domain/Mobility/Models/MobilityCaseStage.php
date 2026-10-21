<?php

namespace App\Domain\Mobility\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Mobility\Enums\MobilityStageStatus;
use App\Support\Concerns\LogsStatusChanges;
use Database\Factories\MobilityCaseStageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['mobility_case_id', 'key', 'position', 'status', 'due_on', 'completed_at', 'completed_by', 'notes', 'next_action', 'blocked_reason'])]
#[UseFactory(MobilityCaseStageFactory::class)]
class MobilityCaseStage extends Model
{
    /** @use HasFactory<MobilityCaseStageFactory> */
    use HasFactory, HasUlids, LogsStatusChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'status' => MobilityStageStatus::class,
            'due_on' => 'date',
            'completed_at' => 'datetime',
        ];
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
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
