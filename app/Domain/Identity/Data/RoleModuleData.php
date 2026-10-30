<?php

namespace App\Domain\Identity\Data;

use Spatie\LaravelData\Data;

class RoleModuleData extends Data
{
    /**
     * @param  list<RolePermissionData>  $permissions
     */
    public function __construct(
        public string $key,
        public string $label,
        public array $permissions,
    ) {}
}
