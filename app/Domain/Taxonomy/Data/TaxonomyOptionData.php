<?php

namespace App\Domain\Taxonomy\Data;

use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Taxonomy\Models\Specialization;
use Spatie\LaravelData\Data;

class TaxonomyOptionData extends Data
{
    public function __construct(
        public string $id,
        public string $slug,
        public string $name,
    ) {}

    public static function fromModel(Occupation|Specialization|Skill $model): self
    {
        return new self($model->id, $model->slug, $model->translatedName());
    }
}
