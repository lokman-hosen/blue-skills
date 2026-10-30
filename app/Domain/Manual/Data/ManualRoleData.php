<?php

namespace App\Domain\Manual\Data;

use App\Domain\Manual\Enums\ManualRole;
use Spatie\LaravelData\Data;

/** A manual the signed-in user may open, for the role picker. */
class ManualRoleData extends Data
{
    public function __construct(
        public string $value,
        public string $label,
        /** Translated via `manual.group.*`. */
        public string $group,
    ) {}

    public static function fromRole(ManualRole $role): self
    {
        return new self($role->value, __("role.{$role->value}"), $role->group());
    }
}
