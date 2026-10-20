<?php

namespace App\Domain\Mobility\Models;

use App\Domain\Applications\Models\Application;
use App\Domain\Mobility\States\MobilityCase\MobilityCaseState;
use App\Support\Concerns\LogsStatusChanges;
use Database\Factories\MobilityCaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\ModelStates\HasStates;

/**
 * Recruitment/mobility case created only after a selection or offer milestone, from a
 * country stage template (product rule 8).
 *
 * @property MobilityCaseState $status
 */
#[Fillable(['application_id', 'template', 'current_stage', 'status'])]
#[UseFactory(MobilityCaseFactory::class)]
class MobilityCase extends Model
{
    /** @use HasFactory<MobilityCaseFactory> */
    use HasFactory, HasStates, HasUlids, LogsStatusChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => MobilityCaseState::class,
        ];
    }

    /**
     * @return list<string>
     */
    protected function loggedStatusAttributes(): array
    {
        return ['status', 'current_stage'];
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return HasMany<MobilityCaseStage, $this>
     */
    public function stages(): HasMany
    {
        return $this->hasMany(MobilityCaseStage::class)->orderBy('position');
    }
}
