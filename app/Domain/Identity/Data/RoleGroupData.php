<?php

namespace App\Domain\Identity\Data;

use Spatie\LaravelData\Data;

class RoleGroupData extends Data
{
    /**
     * @param  list<RoleModuleData>  $modules
     */
    public function __construct(
        public string $key,
        public string $label,
        public array $modules,
    ) {}
}
