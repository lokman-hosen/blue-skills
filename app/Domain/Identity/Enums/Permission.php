<?php

namespace App\Domain\Identity\Enums;

enum Permission: string
{
    // Organization workspace
    case OrganizationUpdate = 'organization.update';
    case TeamManage = 'team.manage';
    case BillingManage = 'billing.manage';
    case JobsView = 'jobs.view';
    case JobsManage = 'jobs.manage';
    case JobsSubmit = 'jobs.submit';
    case ApplicationsView = 'applications.view';
    case ApplicationsManage = 'applications.manage';
    case TalentSearch = 'talent.search';
    case ShortlistsManage = 'shortlists.manage';
    case InterviewsManage = 'interviews.manage';
    case TalentPoolsManage = 'talent_pools.manage';
    case MandatesManage = 'mandates.manage';
    case MobilityManage = 'mobility.manage';
    case ComplianceReview = 'compliance.review';
    case CoursesManage = 'courses.manage';
    case CohortsManage = 'cohorts.manage';
    case GraduatesInvite = 'graduates.invite';
    case AttestationsIssue = 'attestations.issue';
    case AssessmentsRecord = 'assessments.record';
    case PlacementsView = 'placements.view';

    // BLUESKILLS staff
    case AdminAccess = 'admin.access';
    case VerificationsReview = 'verifications.review';
    case JobsModerate = 'jobs.moderate';
    case SkillShowsModerate = 'skill_shows.moderate';
    case TrustManage = 'trust.manage';
    case CrmManage = 'crm.manage';
    case FinanceManage = 'finance.manage';
    case ConfigManage = 'config.manage';
    case TaxonomyManage = 'taxonomy.manage';
    case StaffManage = 'staff.manage';
    case HorizonView = 'horizon.view';
}
