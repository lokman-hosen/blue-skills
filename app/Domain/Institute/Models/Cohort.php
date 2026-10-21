<?php

namespace App\Domain\Institute\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Institute\Enums\CohortStatus;
use App\Domain\Organization\Models\Organization;
use Database\Factories\CohortFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One intake of a course. Graduates join through its invite link and only if they accept
 * (product rule 11, D-082).
 *
 * @property Carbon|null $starts_on
 * @property Carbon|null $ends_on
 */
#[Fillable(['organization_id', 'course_id', 'name', 'starts_on', 'ends_on', 'invite_code', 'status', 'created_by'])]
#[UseFactory(CohortFactory::class)]
class Cohort extends Model
{
    /** @use HasFactory<CohortFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'status' => CohortStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<CohortMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(CohortMember::class);
    }

    public function isOpen(): bool
    {
        return $this->status === CohortStatus::Open;
    }

    /**
     * Eight characters without look-alikes (0/O, 1/I/L), unique across cohorts.
     */
    public static function newInviteCode(): string
    {
        do {
            $code = strtoupper(substr(str_replace(['0', 'O', '1', 'I', 'L'], '', (string) str()->ulid()), -8));
        } while (strlen($code) < 8 || self::query()->where('invite_code', $code)->exists());

        return $code;
    }
}
