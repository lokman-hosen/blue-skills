<?php

namespace App\Domain\Taxonomy\Models;

use App\Domain\Taxonomy\Concerns\HasTranslatedName;
use Database\Factories\OccupationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['sector_id', 'slug', 'name'])]
#[UseFactory(OccupationFactory::class)]
class Occupation extends Model
{
    /** @use HasFactory<OccupationFactory> */
    use HasFactory, HasTranslatedName, HasUlids;

    /**
     * @return BelongsTo<Sector, $this>
     */
    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    /**
     * @return HasMany<Specialization, $this>
     */
    public function specializations(): HasMany
    {
        return $this->hasMany(Specialization::class);
    }

    /**
     * @return HasMany<Skill, $this>
     */
    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class);
    }
}
