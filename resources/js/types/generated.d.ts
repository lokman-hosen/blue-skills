declare namespace App {
    namespace Domain {
        namespace Applications {
            namespace Enums {
                export type ApplicationConsentItem =
                    | 'skill_passport'
                    | 'skill_show'
                    | 'work_experience'
                    | 'training_certificates'
                    | 'passport_scan'
                    | 'phone_number';
                export type ConsentPurpose = 'discovery' | 'application_share' | 'document_share';
            }
        }
        namespace Config {
            namespace Data {
                export type CountryOptionData = {
                    code: string;
                    name: string;
                    dialCode: string;
                };
                export type LocaleData = {
                    code: string;
                    dir: string;
                    translationPending: boolean;
                };
                export type LocaleOptionData = {
                    code: string;
                    nativeName: string;
                    englishName: string;
                    rtl: boolean;
                    status: App.Domain.Config.Enums.LanguageStatus;
                };
            }
            namespace Enums {
                export type CountryRuleKey =
                    | 'languages'
                    | 'agency_eligibility'
                    | 'worker_cost_policy'
                    | 'allowed_worker_cost_categories'
                    | 'mandatory_disclosures'
                    | 'document_types'
                    | 'mobility_stage_template'
                    | 'local_notice';
                export type LanguageStatus = 'active' | 'in_review' | 'planned';
            }
        }
        namespace Identity {
            namespace Data {
                export type AuthUserData = {
                    id: string;
                    name: string;
                    email: string | null;
                    emailVerifiedAt: string | null;
                    type: App.Domain.Identity.Enums.UserType;
                    locale: string;
                    twoFactorEnabled: boolean;
                    staffPermissions: string[];
                };
                export type StaffInvitationData = {
                    id: string;
                    email: string;
                    role: App.Domain.Identity.Enums.StaffRole;
                    expiresAt: string;
                    invitedBy: string | null;
                };
                export type StaffMemberData = {
                    id: string;
                    name: string;
                    email: string | null;
                    role: App.Domain.Identity.Enums.StaffRole | null;
                    twoFactorEnabled: boolean;
                    isYou: boolean;
                };
            }
            namespace Enums {
                export type OrganizationRole =
                    | 'owner'
                    | 'hr_admin'
                    | 'recruiter'
                    | 'hiring_manager'
                    | 'viewer'
                    | 'agency_owner'
                    | 'case_officer'
                    | 'compliance_reviewer'
                    | 'institute_owner'
                    | 'trainer'
                    | 'assessor'
                    | 'placement_officer';
                export type OtpPurpose = 'login' | 'account_deletion';
                export type Permission =
                    | 'organization.update'
                    | 'team.manage'
                    | 'billing.manage'
                    | 'jobs.view'
                    | 'jobs.manage'
                    | 'jobs.submit'
                    | 'applications.view'
                    | 'applications.manage'
                    | 'talent.search'
                    | 'shortlists.manage'
                    | 'interviews.manage'
                    | 'talent_pools.manage'
                    | 'mandates.manage'
                    | 'mobility.manage'
                    | 'compliance.review'
                    | 'courses.manage'
                    | 'cohorts.manage'
                    | 'graduates.invite'
                    | 'attestations.issue'
                    | 'assessments.record'
                    | 'placements.view'
                    | 'admin.access'
                    | 'verifications.review'
                    | 'jobs.moderate'
                    | 'skill_shows.moderate'
                    | 'trust.manage'
                    | 'crm.manage'
                    | 'finance.manage'
                    | 'config.manage'
                    | 'taxonomy.manage'
                    | 'staff.manage'
                    | 'horizon.view';
                export type StaffRole =
                    | 'superuser'
                    | 'verification_officer'
                    | 'trust_officer'
                    | 'crm_agent'
                    | 'finance_officer';
                export type UserType = 'worker' | 'org' | 'staff';
            }
        }
        namespace Jobs {
            namespace Data {
                export type JobBenefitData = {
                    key: string;
                    provision: App.Domain.Jobs.Enums.BenefitProvision | null;
                    details: string | null;
                };
                export type JobCostData = {
                    category: App.Domain.Jobs.Enums.JobCostCategory;
                    description: string | null;
                    amount: App.Support.Data.MoneyData;
                    payer: App.Domain.Jobs.Enums.CostPayer;
                };
                export type JobTruthCardData = {
                    publicCode: string;
                    title: string;
                    occupation: string | null;
                    destinationCountryCode: string;
                    destinationCountryName: string | null;
                    destinationCity: string | null;
                    employerName: string;
                    postedByName: string;
                    postedByAgency: boolean;
                    agencyLicenceNumber: string | null;
                    agencyLicenceExpiresOn: string | null;
                    mandateReference: string | null;
                    mandateHeadcount: number | null;
                    salary: App.Support.Data.MoneyData | null;
                    salaryPeriod: App.Domain.Jobs.Enums.SalaryPeriod | null;
                    hoursPerDay: number | null;
                    daysPerWeek: number | null;
                    overtimeTerms: string | null;
                    contractMonths: number | null;
                    headcount: number;
                    minExperienceYears: number | null;
                    languageRequirements: App.Domain.Jobs.Data.LanguageRequirementData[];
                    skills: string[];
                    benefits: App.Domain.Jobs.Data.JobBenefitData[];
                    otherBenefits: string | null;
                    permitPayer: App.Domain.Jobs.Enums.CostPayer | null;
                    visaPayer: App.Domain.Jobs.Enums.CostPayer | null;
                    costs: App.Domain.Jobs.Data.JobCostData[];
                    joiningFrom: string | null;
                    joiningTo: string | null;
                    deadline: string | null;
                    status: App.Support.Data.StatusData;
                    checks: App.Domain.Jobs.Data.TruthCardCheckData[];
                };
                export type LanguageRequirementData = {
                    code: string;
                    level: string;
                };
                export type TruthCardCheckData = {
                    key: string;
                    tone: string;
                };
            }
            namespace Enums {
                export type BenefitProvision =
                    'provided' | 'allowance' | 'deducted' | 'not_provided';
                export type CostPayer = 'employer' | 'agency' | 'worker';
                export type JobCostCategory =
                    | 'recruitment_fee'
                    | 'visa'
                    | 'work_permit'
                    | 'medical'
                    | 'skills_test'
                    | 'language_test'
                    | 'airfare'
                    | 'document_attestation'
                    | 'training'
                    | 'accommodation'
                    | 'other';
                export type MandateStatus =
                    'pending' | 'verified' | 'rejected' | 'expired' | 'revoked';
                export type SalaryPeriod = 'hour' | 'day' | 'week' | 'month';
            }
        }
        namespace Mobility {
            namespace Enums {
                export type MobilityStageStatus =
                    'pending' | 'in_progress' | 'done' | 'blocked' | 'skipped';
            }
        }
        namespace Organization {
            namespace Data {
                export type EvidenceDocumentData = {
                    id: string;
                    name: string;
                    sizeKb: number;
                    uploadedAt: string | null;
                };
                export type OrganizationSummaryData = {
                    id: string;
                    displayName: string;
                    type: App.Domain.Organization.Enums.OrganizationType;
                    countryCode: string;
                    city: string | null;
                    verification: App.Support.Data.StatusData;
                };
                export type RegisterOrganizationData = {
                    type: App.Domain.Organization.Enums.OrganizationType;
                    legalName: string;
                    displayName: string;
                    countryCode: string;
                    city: string | null;
                    registrationNumber: string | null;
                    licenceNumber: string | null;
                    licenceExpiresOn: string | null;
                    title: string | null;
                };
                export type TeamInvitationData = {
                    id: string;
                    email: string | null;
                    role: App.Domain.Identity.Enums.OrganizationRole;
                    expiresAt: string;
                    invitedBy: string | null;
                    organization: App.Domain.Organization.Data.OrganizationSummaryData;
                };
                export type TeamMemberData = {
                    id: string;
                    name: string;
                    email: string | null;
                    title: string | null;
                    role: App.Domain.Identity.Enums.OrganizationRole | null;
                    joinedAt: string | null;
                    isYou: boolean;
                };
            }
            namespace Enums {
                export type OrganizationType = 'employer' | 'agency' | 'institute';
            }
        }
        namespace Trust {
            namespace Enums {
                export type ReportCategory =
                    | 'fake_job'
                    | 'unauthorized_payment'
                    | 'misleading_salary'
                    | 'impersonation'
                    | 'harassment'
                    | 'fake_credential'
                    | 'other';
            }
        }
        namespace Verification {
            namespace Enums {
                export type DocumentType =
                    | 'passport'
                    | 'national_id'
                    | 'photo'
                    | 'certificate'
                    | 'employer_reference'
                    | 'trade_licence'
                    | 'agency_licence'
                    | 'accreditation'
                    | 'contract'
                    | 'medical'
                    | 'police_clearance'
                    | 'work_permit'
                    | 'visa'
                    | 'other';
                export type EvidenceType =
                    | 'identity'
                    | 'passport'
                    | 'employer_reference'
                    | 'certificate'
                    | 'training'
                    | 'trade_assessment'
                    | 'work_experience';
                export type VerificationDecision = 'approved' | 'rejected' | 'needs_info';
                export type VerificationType =
                    | 'organization'
                    | 'agency_licence'
                    | 'institute_accreditation'
                    | 'job_mandate'
                    | 'job'
                    | 'worker_evidence'
                    | 'skill_show';
            }
        }
        namespace Worker {
            namespace Enums {
                export type Availability =
                    | 'immediately'
                    | 'within_30_days'
                    | 'within_60_days'
                    | 'within_90_days'
                    | 'not_available';
                export type LanguageProficiency = 'basic' | 'conversational' | 'fluent' | 'native';
                export type ModerationStatus =
                    'processing' | 'failed' | 'pending' | 'approved' | 'rejected';
                export type PassportStatus = 'none' | 'applied' | 'ready' | 'expired';
                export type ProfileVisibility =
                    'employers_and_agencies' | 'applications_only' | 'hidden';
                export type SkillSource = 'voice' | 'typed' | 'institute';
                export type VoiceAnswerStatus =
                    'pending' | 'transcribed' | 'extracted' | 'confirmed' | 'discarded' | 'failed';
                export type VoiceQuestion =
                    | 'occupation'
                    | 'experience'
                    | 'countries'
                    | 'skills'
                    | 'passport'
                    | 'availability'
                    | 'destinations';
            }
        }
    }
    namespace Support {
        namespace Data {
            export type MoneyData = {
                amountMinor: number;
                currency: string;
            };
            export type StatusData = {
                value: string;
                label: string;
                tone: string;
            };
        }
    }
}
declare namespace Illuminate {
    export type CursorPaginator<TKey, TValue> = {
        data: TKey extends string ? Record<TKey, TValue> : TValue[];
        links: {
            url: string | null;
            label: string;
            active: boolean;
        }[];
        meta: {
            path: string;
            per_page: number;
            next_cursor: string | null;
            next_page_url: string | null;
            prev_cursor: string | null;
            prev_page_url: string | null;
        };
    };
    export type CursorPaginatorInterface<TKey, TValue> = Illuminate.CursorPaginator<TKey, TValue>;
    export type LengthAwarePaginator<TKey, TValue> = {
        data: TKey extends string ? Record<TKey, TValue> : TValue[];
        links: {
            url: string | null;
            label: string;
            active: boolean;
        }[];
        meta: {
            total: number;
            current_page: number;
            first_page_url: string;
            from: number | null;
            last_page: number;
            last_page_url: string;
            next_page_url: string | null;
            path: string;
            per_page: number;
            prev_page_url: string | null;
            to: number | null;
        };
    };
    export type LengthAwarePaginatorInterface<TKey, TValue> = Illuminate.LengthAwarePaginator<
        TKey,
        TValue
    >;
}
declare namespace Spatie {
    namespace LaravelData {
        export type CursorPaginatedDataCollection<TKey, TValue> = Illuminate.CursorPaginator<
            TKey,
            TValue
        >;
        export type PaginatedDataCollection<TKey, TValue> = Illuminate.LengthAwarePaginator<
            TKey,
            TValue
        >;
    }
}
