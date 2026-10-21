<?php

namespace App\Domain\Institute\Models;

use App\Domain\Organization\Models\Organization;
use App\Domain\Taxonomy\Models\Occupation;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A training course an institute runs; each intake is a cohort (spec §7).
 */
#[Fillable(['organization_id', 'title', 'occupation_id', 'duration_weeks', 'description'])]
#[UseFactory(CourseFactory::class)]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['duration_weeks' => 'integer'];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Occupation, $this>
     */
    public function occupation(): BelongsTo
    {
        return $this->belongsTo(Occupation::class);
    }

    /**
     * @return HasMany<Cohort, $this>
     */
    public function cohorts(): HasMany
    {
        return $this->hasMany(Cohort::class)->orderByDesc('starts_on');
    }
}
