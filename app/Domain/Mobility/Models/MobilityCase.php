<?php

namespace App\Domain\Mobility\Models;

use App\Domain\Applications\Models\Application;
use App\Domain\Identity\Models\User;
use App\Domain\Mobility\Enums\CaseClosedReason;
use App\Domain\Mobility\States\MobilityCase\MobilityCaseState;
use App\Domain\Mobility\States\MobilityCase\Open;
use App\Domain\Organization\Models\Organization;
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
#[Fillable([
    'application_id', 'template', 'current_stage', 'status', 'reference', 'managing_organization_id', 'employer_organization_id',
    'agency_organization_id', 'handler_user_id', 'closed_reason', 'closed_at',
])]
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
            'closed_reason' => CaseClosedReason::class,
            'closed_at' => 'datetime',
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

    /**
     * @return HasMany<MobilityCaseEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(MobilityCaseEvent::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * @return HasMany<CaseDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(CaseDocument::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * The organization running the case: the agency for agency jobs, else the employer (D-074).
     *
     * @return BelongsTo<Organization, $this>
     */
    public function managingOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'managing_organization_id');
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'employer_organization_id');
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'agency_organization_id');
    }

    /**
     * The person at the managing organization the worker sees as "Handled by".
     *
     * @return BelongsTo<User, $this>
     */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handler_user_id');
    }

    public function isOpen(): bool
    {
        return $this->status->equals(Open::class);
    }

    public function currentStage(): ?MobilityCaseStage
    {
        return $this->stages->firstWhere('key', $this->current_stage);
    }
}
