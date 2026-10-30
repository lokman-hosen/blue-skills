<?php

namespace App\Domain\Manual\Enums;

use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Enums\StaffRole;

/**
 * One user manual per role (D-110). Cases are listed in reading order: the worker first, then
 * each workspace from least to most access, with the Superuser last. The value is the
 * manual's file name in `resources/manuals/{locale}/` and its URL segment.
 */
enum ManualRole: string
{
    case Worker = 'worker';
    case Viewer = 'viewer';
    case HiringManager = 'hiring_manager';
    case Recruiter = 'recruiter';
    case HrAdmin = 'hr_admin';
    case Owner = 'owner';
    case ComplianceReviewer = 'compliance_reviewer';
    case CaseOfficer = 'case_officer';
    case AgencyOwner = 'agency_owner';
    case Assessor = 'assessor';
    case Trainer = 'trainer';
    case PlacementOfficer = 'placement_officer';
    case InstituteOwner = 'institute_owner';
    case CrmAgent = 'crm_agent';
    case FinanceOfficer = 'finance_officer';
    case TrustOfficer = 'trust_officer';
    case VerificationOfficer = 'verification_officer';
    case Superuser = 'superuser';

    /**
     * English label; translate with __() at the presentation edge.
     */
    public function label(): string
    {
        return $this === self::Worker
            ? 'Worker'
            : (OrganizationRole::tryFrom($this->value)?->label() ?? StaffRole::from($this->value)->label());
    }

    /**
     * The workspace the role belongs to, as a `manual.group.*` translation suffix.
     */
    public function group(): string
    {
        if ($this === self::Worker) {
            return 'worker';
        }

        return OrganizationRole::tryFrom($this->value)?->organizationType()->value ?? 'staff';
    }

    public static function fromOrganizationRole(OrganizationRole $role): self
    {
        return self::from($role->value);
    }

    public static function fromStaffRole(StaffRole $role): self
    {
        return self::from($role->value);
    }
}
