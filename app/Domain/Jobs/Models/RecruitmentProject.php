<?php

namespace App\Domain\Jobs\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Jobs\States\RecruitmentProject\Active;
use App\Domain\Jobs\States\RecruitmentProject\RecruitmentProjectState;
use App\Domain\Organization\Models\Organization;
use App\Support\Concerns\LogsStatusChanges;
use Database\Factories\RecruitmentProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\ModelStates\HasStates;

/**
 * A bulk-hiring project grouping one or more jobs with a target headcount, source countries and
 * a team (spec §6). Stage counts and deployment numbers come from the jobs' applications and
 * mobility cases.
 *
 * @property RecruitmentProjectState $status
 * @property list<string> $source_country_codes
 * @property Carbon|null $target_date
 */
#[Fillable([
    'organization_id', 'reference', 'name', 'target_headcount', 'destination_country_code', 'source_country_codes',
    'status', 'target_date', 'created_by',
])]
#[UseFactory(RecruitmentProjectFactory::class)]
class RecruitmentProject extends Model
{
    /** @use HasFactory<RecruitmentProjectFactory> */
    use HasFactory, HasStates, HasUlids, LogsStatusChanges;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RecruitmentProjectState::class,
            'source_country_codes' => 'array',
            'target_headcount' => 'integer',
            'target_date' => 'date',
        ];
    }

    /**
     * @return list<string>
     */
    protected function loggedStatusAttributes(): array
    {
        return ['status', 'target_headcount'];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany<JobPosting, $this>
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(JobPosting::class);
    }

    /**
     * The assigned team.
     *
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'recruitment_project_members')->withTimestamps();
    }

    public function isActive(): bool
    {
        return $this->status->equals(Active::class);
    }

    /**
     * RP-0042: a running number within the organization.
     */
    public static function newReference(string $organizationId): string
    {
        $last = self::query()->where('organization_id', $organizationId)->max('reference');
        $next = $last !== null ? ((int) substr((string) $last, 3)) + 1 : 1;

        return 'RP-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
