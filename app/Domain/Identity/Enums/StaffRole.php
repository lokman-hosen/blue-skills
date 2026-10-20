<?php

namespace App\Domain\Identity\Enums;

/**
 * Global BLUESKILLS staff roles, assigned in the platform team context (see PlatformTeam).
 */
enum StaffRole: string
{
    case Superuser = 'superuser';
    case VerificationOfficer = 'verification_officer';
    case TrustOfficer = 'trust_officer';
    case CrmAgent = 'crm_agent';
    case FinanceOfficer = 'finance_officer';

    public function label(): string
    {
        return match ($this) {
            self::Superuser => 'Superuser',
            self::VerificationOfficer => 'Verification Officer',
            self::TrustOfficer => 'Trust Officer',
            self::CrmAgent => 'CRM Agent',
            self::FinanceOfficer => 'Finance Officer',
        };
    }

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Superuser => [
                Permission::AdminAccess, Permission::VerificationsReview, Permission::JobsModerate,
                Permission::SkillShowsModerate, Permission::TrustManage, Permission::CrmManage,
                Permission::FinanceManage, Permission::ConfigManage, Permission::TaxonomyManage,
                Permission::StaffManage, Permission::HorizonView,
            ],
            self::VerificationOfficer => [
                Permission::AdminAccess, Permission::VerificationsReview,
                Permission::JobsModerate, Permission::SkillShowsModerate,
            ],
            self::TrustOfficer => [Permission::AdminAccess, Permission::TrustManage, Permission::JobsModerate],
            self::CrmAgent => [Permission::AdminAccess, Permission::CrmManage],
            self::FinanceOfficer => [Permission::AdminAccess, Permission::FinanceManage],
        };
    }
}
