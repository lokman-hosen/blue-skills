<?php

namespace App\Domain\Identity\Data;

use Spatie\LaravelData\Data;

/** One role in Admin › Roles & permissions (D-112). */
class RoleSummaryData extends Data
{
    public function __construct(
        public string $name,
        public string $label,
        /** `staff`, `employer`, `agency` or `institute`; translated via `roles.kind.*`. */
        public string $kind,
        /** People holding the role, in any organization. */
        public int $members,
        /** How many of the permissions the editor offers the role holds. */
        public int $granted,
        public int $offered,
        /** Differs from the defaults in code. */
        public bool $changed,
        /** The Superuser: holds everything, can't be edited. */
        public bool $locked,
        /** Created by the Superuser: can be renamed and deleted, has no defaults (D-113). */
        public bool $custom = false,
        public ?string $description = null,
        /** The role it started from; its manual follows that role. */
        public ?string $basedOn = null,
        public ?string $basedOnLabel = null,
        /** Unanswered invitations that would give someone this role. */
        public int $invitations = 0,
    ) {}
}
