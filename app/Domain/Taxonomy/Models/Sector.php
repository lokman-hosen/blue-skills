<?php

namespace App\Domain\Taxonomy\Models;

use App\Domain\Taxonomy\Concerns\HasTranslatedName;
use Database\Factories\SectorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'sort_order'])]
#[UseFactory(SectorFactory::class)]
class Sector extends Model
{
    /** @use HasFactory<SectorFactory> */
    use HasFactory, HasTranslatedName, HasUlids;

    /**
     * @return HasMany<Occupation, $this>
     */
    public function occupations(): HasMany
    {
        return $this->hasMany(Occupation::class);
    }
}
