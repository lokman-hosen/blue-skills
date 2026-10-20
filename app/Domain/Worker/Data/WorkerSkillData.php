<?php

namespace App\Domain\Worker\Data;

use App\Domain\Worker\Enums\SkillSource;
use Spatie\LaravelData\Data;

class WorkerSkillData extends Data
{
    public function __construct(
        public string $id,
        public string $slug,
        public string $name,
        public SkillSource $source,
    ) {}
}
