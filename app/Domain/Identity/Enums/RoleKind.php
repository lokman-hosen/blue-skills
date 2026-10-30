<?php

namespace App\Domain\Identity\Enums;

use App\Domain\Organization\Enums\OrganizationType;

/**
 * Which side of BLUESKILLS a role belongs to: the staff workspace, or one kind of organization.
 * The roles & permissions editor only offers permissions that fit the kind (D-112).
 */
enum RoleKind: string
{
    case Staff = 'staff';
    case Employer = 'employer';
    case Agency = 'agency';
    case Institute = 'institute';

    public static function of(string $roleName): ?self
    {
        if (StaffRole::tryFrom($roleName) !== null) {
            return self::Staff;
        }

        return match (OrganizationRole::tryFrom($roleName)?->organizationType()) {
            OrganizationType::Employer => self::Employer,
            OrganizationType::Agency => self::Agency,
            OrganizationType::Institute => self::Institute,
            null => null,
        };
    }

    /**
     * The role names of this kind, in the order the roles are listed elsewhere.
     *
     * @return list<string>
     */
    public function roleNames(): array
    {
        return match ($this) {
            self::Staff => array_map(fn (StaffRole $role) => $role->value, StaffRole::cases()),
            self::Employer => array_map(fn (OrganizationRole $role) => $role->value, OrganizationRole::forType(OrganizationType::Employer)),
            self::Agency => array_map(fn (OrganizationRole $role) => $role->value, OrganizationRole::forType(OrganizationType::Agency)),
            self::Institute => array_map(fn (OrganizationRole $role) => $role->value, OrganizationRole::forType(OrganizationType::Institute)),
        };
    }
}
