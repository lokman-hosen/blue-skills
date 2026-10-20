<?php

namespace App\Domain\Identity\Enums;

use App\Domain\Organization\Enums\OrganizationType;

/**
 * Roles a member holds inside one organization (spatie-permission team = organization).
 */
enum OrganizationRole: string
{
    case Owner = 'owner';
    case HrAdmin = 'hr_admin';
    case Recruiter = 'recruiter';
    case HiringManager = 'hiring_manager';
    case Viewer = 'viewer';
    case AgencyOwner = 'agency_owner';
    case CaseOfficer = 'case_officer';
    case ComplianceReviewer = 'compliance_reviewer';
    case InstituteOwner = 'institute_owner';
    case Trainer = 'trainer';
    case Assessor = 'assessor';
    case PlacementOfficer = 'placement_officer';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::HrAdmin => 'HR Admin',
            self::Recruiter => 'Recruiter',
            self::HiringManager => 'Hiring Manager',
            self::Viewer => 'Viewer',
            self::AgencyOwner => 'Agency Owner',
            self::CaseOfficer => 'Case Officer',
            self::ComplianceReviewer => 'Compliance Reviewer',
            self::InstituteOwner => 'Institute Owner',
            self::Trainer => 'Trainer',
            self::Assessor => 'Assessor',
            self::PlacementOfficer => 'Placement Officer',
        };
    }

    public function organizationType(): OrganizationType
    {
        return match ($this) {
            self::Owner, self::HrAdmin, self::Recruiter, self::HiringManager, self::Viewer => OrganizationType::Employer,
            self::AgencyOwner, self::CaseOfficer, self::ComplianceReviewer => OrganizationType::Agency,
            self::InstituteOwner, self::Trainer, self::Assessor, self::PlacementOfficer => OrganizationType::Institute,
        };
    }

    /**
     * @return list<self>
     */
    public static function forType(OrganizationType $type): array
    {
        return array_values(array_filter(self::cases(), fn (self $role) => $role->organizationType() === $type));
    }

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        $hiring = [
            Permission::JobsView, Permission::JobsManage, Permission::JobsSubmit,
            Permission::ApplicationsView, Permission::ApplicationsManage,
            Permission::TalentSearch, Permission::ShortlistsManage, Permission::InterviewsManage,
        ];
        $admin = [Permission::OrganizationUpdate, Permission::TeamManage, Permission::BillingManage];

        return match ($this) {
            self::Owner => [...$admin, ...$hiring, Permission::TalentPoolsManage],
            self::HrAdmin => [Permission::TeamManage, ...$hiring, Permission::TalentPoolsManage],
            self::Recruiter => $hiring,
            self::HiringManager => [
                Permission::JobsView, Permission::ApplicationsView,
                Permission::ShortlistsManage, Permission::InterviewsManage,
            ],
            self::Viewer => [Permission::JobsView, Permission::ApplicationsView],
            self::AgencyOwner => [
                ...$admin, ...$hiring, Permission::TalentPoolsManage,
                Permission::MandatesManage, Permission::MobilityManage, Permission::ComplianceReview,
            ],
            self::CaseOfficer => [...$hiring, Permission::MobilityManage],
            self::ComplianceReviewer => [
                Permission::JobsView, Permission::ApplicationsView,
                Permission::MandatesManage, Permission::ComplianceReview,
            ],
            self::InstituteOwner => [
                ...$admin, Permission::CoursesManage, Permission::CohortsManage, Permission::GraduatesInvite,
                Permission::AttestationsIssue, Permission::AssessmentsRecord, Permission::TalentPoolsManage,
                Permission::PlacementsView,
            ],
            self::Trainer => [Permission::CoursesManage, Permission::CohortsManage, Permission::AttestationsIssue],
            self::Assessor => [Permission::AssessmentsRecord, Permission::AttestationsIssue],
            self::PlacementOfficer => [
                Permission::CohortsManage, Permission::GraduatesInvite,
                Permission::TalentPoolsManage, Permission::PlacementsView,
            ],
        };
    }
}
