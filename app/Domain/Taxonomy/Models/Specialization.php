<?php

namespace App\Domain\Taxonomy\Models;

use App\Domain\Taxonomy\Concerns\HasTranslatedName;
use Database\Factories\SpecializationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['occupation_id', 'slug', 'name'])]
#[UseFactory(SpecializationFactory::class)]
class Specialization extends Model
{
    /** @use HasFactory<SpecializationFactory> */
    use HasFactory, HasTranslatedName, HasUlids;

    /**
     * @return BelongsTo<Occupation, $this>
     */
    public function occupation(): BelongsTo
    {
        return $this->belongsTo(Occupation::class);
    }
}
