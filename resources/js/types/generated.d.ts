declare namespace App {
    namespace Domain {
        namespace Applications {
            namespace Data {
                export type ApplicantCountsData = {
                    total: number;
                    new: number;
                    shortlisted: number;
                    interviewsUpcoming: number;
                };
                export type ApplicantData = {
                    id: string;
                    jobCode: string;
                    jobTitle: string;
                    status: App.Support.Data.StatusData;
                    closed: boolean;
                    appliedAt: string;
                    snapshotTakenAt: string | null;
                    snapshot: App.Domain.Applications.Data.ProfileSnapshotData | null;
                    skillShow: App.Domain.Worker.Data.SkillShowData | null;
                    phone: string | null;
                    passportScanUrl: string | null;
                    consents: App.Domain.Applications.Data.ConsentItemData[];
                    stages: App.Domain.Applications.Data.StageData[];
                    events: App.Domain.Applications.Data.StageEventData[];
                    interviews: App.Domain.Applications.Data.InterviewData[];
                    offers: App.Domain.Applications.Data.OfferData[];
                    assignee: App.Domain.Applications.Data.MemberOptionData | null;
                    nextStages: string[];
                    canOffer: boolean;
                    jobSalary: App.Support.Data.MoneyData | null;
                    jobSalaryPeriod: App.Domain.Jobs.Enums.SalaryPeriod | null;
                };
                export type ApplicantListItemData = {
                    id: string;
                    name: string;
                    reference: string;
                    occupation: string | null;
                    yearsExperience: number | null;
                    currentCountry: string | null;
                    status: App.Support.Data.StatusData;
                    appliedAt: string;
                    met: number;
                    total: number;
                    hasSkillShow: boolean;
                    assigneeName: string | null;
                    upcomingInterviewAt: string | null;
                };
                export type ConsentItemData = {
                    item: App.Domain.Applications.Enums.ApplicationConsentItem;
                    active: boolean;
                    grantedAt: string | null;
                    revokedAt: string | null;
                    revocable: boolean;
                };
                export type InterviewData = {
                    id: string;
                    scheduledAt: string;
                    durationMinutes: number;
                    mode: App.Domain.Applications.Enums.InterviewMode;
                    location: string | null;
                    instructions: string | null;
                    outcome: App.Domain.Applications.Enums.InterviewOutcome | null;
                    upcoming: boolean;
                };
                export type MemberOptionData = {
                    id: string;
                    name: string;
                };
                export type OfferData = {
                    id: string;
                    salary: App.Support.Data.MoneyData;
                    salaryPeriod: App.Domain.Jobs.Enums.SalaryPeriod;
                    startOn: string | null;
                    respondBy: string | null;
                    note: string | null;
                    status: App.Domain.Applications.Enums.OfferStatus;
                    sentAt: string;
                    respondedAt: string | null;
                };
                export type ProfileSnapshotData = {
                    reference: string;
                    name: string;
                    headline: string | null;
                    currentCountry: App.Domain.Config.Data.CountryOptionData | null;
                    occupation: App.Domain.Taxonomy.Data.TaxonomyOptionData | null;
                    specialization: App.Domain.Taxonomy.Data.TaxonomyOptionData | null;
                    yearsExperience: number | null;
                    passportStatus: App.Domain.Worker.Enums.PassportStatus | null;
                    passportValidUntilYear: number | null;
                    availability: App.Domain.Worker.Enums.Availability | null;
                    skills: App.Domain.Worker.Data.WorkerSkillData[];
                    languages: App.Domain.Worker.Data.WorkerLanguageData[];
                    destinations: App.Domain.Config.Data.CountryOptionData[];
                    experiences: App.Domain.Worker.Data.WorkerExperienceData[];
                    credentials: App.Domain.Worker.Data.EvidenceSummaryData[];
                    match: App.Domain.Jobs.Data.MatchSummaryData;
                    takenAt: string;
                };
                export type ShareableItemData = {
                    item: App.Domain.Applications.Enums.ApplicationConsentItem;
                    required: boolean;
                    sensitive: boolean;
                    available: boolean;
                    sharedByDefault: boolean;
                    detail: string;
                    params: Record<string, string | number>;
                };
                export type ShortlistData = {
                    id: string;
                    name: string;
                    jobCode: string | null;
                    jobTitle: string | null;
                    entries: number;
                    createdBy: string | null;
                    createdAt: string;
                };
                export type ShortlistEntryData = {
                    id: string;
                    note: string | null;
                    addedAt: string;
                    addedBy: string | null;
                    worker: App.Domain.Applications.Data.TalentCardData | null;
                };
                export type StageData = {
                    key: string;
                    status: string;
                    date: string | null;
                };
                export type StageEventData = {
                    id: string;
                    from: string | null;
                    to: string;
                    at: string;
                    reasonCode: string | null;
                    actorName: string | null;
                    note: string | null;
                };
                export type TalentCardData = {
                    reference: string;
                    name: string;
                    headline: string | null;
                    occupation: string | null;
                    yearsExperience: number | null;
                    currentCountry: string | null;
                    countriesWorked: string[];
                    skills: string[];
                    languages: string[];
                    passportStatus: App.Domain.Worker.Enums.PassportStatus | null;
                    availability: App.Domain.Worker.Enums.Availability | null;
                    verifiedEvidence: number;
                    skillShowPosterUrl: string | null;
                    match: App.Domain.Jobs.Data.MatchSummaryData | null;
                };
                export type TalentProfileData = {
                    reference: string;
                    name: string;
                    headline: string | null;
                    currentCountry: App.Domain.Config.Data.CountryOptionData | null;
                    occupation: App.Domain.Taxonomy.Data.TaxonomyOptionData | null;
                    specialization: App.Domain.Taxonomy.Data.TaxonomyOptionData | null;
                    yearsExperience: number | null;
                    passportStatus: App.Domain.Worker.Enums.PassportStatus | null;
                    passportValidUntilYear: number | null;
                    availability: App.Domain.Worker.Enums.Availability | null;
                    skills: App.Domain.Worker.Data.WorkerSkillData[];
                    languages: App.Domain.Worker.Data.WorkerLanguageData[];
                    destinations: App.Domain.Config.Data.CountryOptionData[];
                    experiences: App.Domain.Worker.Data.WorkerExperienceData[];
                    credentials: App.Domain.Worker.Data.EvidenceSummaryData[];
                    skillShow: App.Domain.Worker.Data.SkillShowData | null;
                };
                export type WorkerApplicationData = {
                    id: string;
                    job: App.Domain.Jobs.Data.JobListingData;
                    status: App.Support.Data.StatusData;
                    appliedAt: string;
                    closed: boolean;
                    handledBy: string;
                    nextStep: string;
                    stages: App.Domain.Applications.Data.StageData[];
                    events: App.Domain.Applications.Data.StageEventData[];
                    interviews: App.Domain.Applications.Data.InterviewData[];
                    offers: App.Domain.Applications.Data.OfferData[];
                    consents: App.Domain.Applications.Data.ConsentItemData[];
                    jobChangedAt: string | null;
                    snapshotTakenAt: string | null;
                };
                export type WorkerApplicationListItemData = {
                    id: string;
                    job: App.Domain.Jobs.Data.JobListingData;
                    status: App.Support.Data.StatusData;
                    appliedAt: string;
                    nextStep: string;
                    upcomingInterviewAt: string | null;
                    jobChanged: boolean;
                    closed: boolean;
                };
            }
            namespace Enums {
                export type ApplicationConsentItem =
                    | 'skill_passport'
                    | 'skill_show'
                    | 'work_experience'
                    | 'training_certificates'
                    | 'passport_scan'
                    | 'phone_number';
                export type ConsentPurpose = 'discovery' | 'application_share' | 'document_share';
                export type InterviewMode = 'in_person' | 'video' | 'phone';
                export type InterviewOutcome = 'passed' | 'not_passed' | 'no_show' | 'cancelled';
                export type InvitationStatus = 'sent' | 'applied' | 'declined' | 'expired';
                export type OfferStatus = 'sent' | 'accepted' | 'declined' | 'withdrawn';
                export type RejectionReason =
                    | 'requirements_not_met'
                    | 'experience_insufficient'
                    | 'skills_not_shown'
                    | 'interview_not_passed'
                    | 'assessment_not_passed'
                    | 'positions_filled'
                    | 'not_available'
                    | 'other';
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
                export type DestinationOptionData = {
                    code: string;
                    name: string;
                    currency: string;
                    allowedWorkerCosts: App.Domain.Jobs.Enums.JobCostCategory[];
                    workerCostPolicy: string | null;
                    mandatoryFields: string[];
                };
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
                export type JobCostFormData = {
                    category: App.Domain.Jobs.Enums.JobCostCategory;
                    description: string | null;
                    amount: number;
                    currency: string;
                    payer: App.Domain.Jobs.Enums.CostPayer;
                };
                export type JobCountsData = {
                    live: number;
                    inReview: number;
                    needsChanges: number;
                    drafts: number;
                };
                export type JobFormData = {
                    title: string;
                    occupationId: string;
                    headcount: number;
                    destinationCountryCode: string;
                    destinationCity: string | null;
                    joiningFrom: string | null;
                    joiningTo: string | null;
                    skillIds: string[];
                    minExperienceYears: number | null;
                    languageRequirements: App.Domain.Jobs.Data.LanguageRequirementData[];
                    salaryAmount: number | null;
                    salaryCurrency: string | null;
                    salaryPeriod: App.Domain.Jobs.Enums.SalaryPeriod | null;
                    hoursPerDay: number | null;
                    daysPerWeek: number | null;
                    overtimeTerms: string | null;
                    contractMonths: number | null;
                    benefits: Record<string, App.Domain.Jobs.Enums.BenefitProvision | null>;
                    benefitDetails: Record<string, string | null>;
                    otherBenefits: string | null;
                    permitPayer: App.Domain.Jobs.Enums.CostPayer | null;
                    visaPayer: App.Domain.Jobs.Enums.CostPayer | null;
                    deadline: string | null;
                    costs: App.Domain.Jobs.Data.JobCostFormData[];
                };
                export type JobFormOptionsData = {
                    occupations: App.Domain.Taxonomy.Data.OccupationOptionData[];
                    skills: App.Domain.Taxonomy.Data.TaxonomyOptionData[];
                    destinations: App.Domain.Jobs.Data.DestinationOptionData[];
                    currencies: string[];
                };
                export type JobListItemData = {
                    code: string;
                    title: string;
                    occupation: string | null;
                    destinationCountryCode: string;
                    destinationCountryName: string | null;
                    destinationCity: string | null;
                    headcount: number;
                    status: App.Support.Data.StatusData;
                    deadline: string | null;
                    updatedAt: string | null;
                    publishedAt: string | null;
                    lastDecision: App.Domain.Verification.Data.ReviewDecisionData | null;
                    editable: boolean;
                    applicants: number | null;
                };
                export type JobListingData = {
                    code: string;
                    title: string;
                    employerName: string;
                    employerVerified: boolean;
                    occupation: string | null;
                    destinationCountryCode: string;
                    destinationCountryName: string | null;
                    destinationCity: string | null;
                    salary: App.Support.Data.MoneyData | null;
                    salaryPeriod: App.Domain.Jobs.Enums.SalaryPeriod | null;
                    accommodation: App.Domain.Jobs.Enums.BenefitProvision | null;
                    workerPaysCosts: boolean;
                    deadline: string | null;
                    publishedAt: string | null;
                    match: App.Domain.Jobs.Data.MatchSummaryData | null;
                };
                export type JobReviewItemData = {
                    reviewId: string;
                    reviewStatus: App.Support.Data.StatusData;
                    code: string;
                    title: string;
                    employerName: string;
                    employerVerification: App.Support.Data.StatusData;
                    destination: string | null;
                    notes: string | null;
                    problemCount: number;
                    submittedAt: string | null;
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
                export type MatchRequirementData = {
                    key: string;
                    params: Record<string, string | number>;
                    met: boolean;
                };
                export type MatchSummaryData = {
                    met: number;
                    total: number;
                    requirements: App.Domain.Jobs.Data.MatchRequirementData[];
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
        namespace Messaging {
            namespace Data {
                export type AttachmentData = {
                    id: string;
                    name: string;
                    size: number;
                    url: string | null;
                };
                export type ConversationData = {
                    id: string;
                    applicationId: string;
                    jobTitle: string;
                    jobCode: string;
                    counterpart: string;
                    writable: boolean;
                    canPost: boolean;
                    messages: App.Domain.Messaging.Data.MessageData[];
                };
                export type ConversationListItemData = {
                    id: string;
                    jobTitle: string;
                    jobCode: string;
                    counterpart: string;
                    preview: string | null;
                    lastMessageAt: string | null;
                    unread: number;
                };
                export type MessageData = {
                    id: string;
                    side: App.Domain.Messaging.Enums.MessageSide;
                    senderName: string;
                    mine: boolean;
                    body: string;
                    at: string;
                    attachments: App.Domain.Messaging.Data.AttachmentData[];
                };
                export type NotificationData = {
                    id: string;
                    event: App.Domain.Messaging.Enums.NotificationEvent;
                    title: string;
                    body: string;
                    url: string;
                    read: boolean;
                    createdAt: string;
                };
            }
            namespace Enums {
                export type DeliveryChannel = 'whatsapp' | 'sms' | 'mail';
                export type DeliveryStatus = 'held' | 'queued' | 'sent' | 'failed' | 'skipped';
                export type MessageSide = 'worker' | 'organization' | 'system';
                export type NotificationEvent =
                    | 'shortlisted'
                    | 'interview_scheduled'
                    | 'offer_received'
                    | 'application_rejected'
                    | 'job_changed'
                    | 'job_invitation'
                    | 'worker_message'
                    | 'new_applicants'
                    | 'application_answered'
                    | 'organization_message';
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
                    url: string | null;
                };
                export type OrganizationReviewData = {
                    reviewId: string;
                    reviewStatus: App.Support.Data.StatusData;
                    organizationId: string;
                    displayName: string;
                    legalName: string;
                    type: App.Domain.Organization.Enums.OrganizationType;
                    countryCode: string;
                    city: string | null;
                    registrationNumber: string | null;
                    licenceNumber: string | null;
                    licenceExpiresOn: string | null;
                    verification: App.Support.Data.StatusData;
                    flags: string | null;
                    evidenceCount: number;
                    submittedAt: string | null;
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
        namespace Taxonomy {
            namespace Data {
                export type OccupationOptionData = {
                    id: string;
                    slug: string;
                    name: string;
                    sector: string;
                    specializations: App.Domain.Taxonomy.Data.TaxonomyOptionData[];
                    skills: App.Domain.Taxonomy.Data.TaxonomyOptionData[];
                };
                export type TaxonomyOptionData = {
                    id: string;
                    slug: string;
                    name: string;
                };
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
            namespace Data {
                export type ReviewDecisionData = {
                    decision: App.Domain.Verification.Enums.VerificationDecision;
                    reasonCode: string | null;
                    note: string | null;
                    decidedAt: string | null;
                    reviewerName: string | null;
                };
            }
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
                export type ReviewReason =
                    | 'document_unreadable'
                    | 'document_expired'
                    | 'details_mismatch'
                    | 'not_a_registered_business'
                    | 'licence_out_of_scope'
                    | 'unlawful_worker_fee'
                    | 'conditions_unclear'
                    | 'salary_implausible'
                    | 'duplicate_job'
                    | 'prohibited_content'
                    | 'more_information_needed';
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
            namespace Data {
                export type ConsentRecordData = {
                    id: string;
                    purpose: App.Domain.Applications.Enums.ConsentPurpose;
                    grantedAt: string;
                    revokedAt: string | null;
                };
                export type EvidenceSummaryData = {
                    id: string;
                    type: App.Domain.Verification.Enums.EvidenceType;
                    title: string | null;
                    state: string;
                    reasonCode: string | null;
                    expiresOn: string | null;
                    removable: boolean;
                };
                export type IdentityDocumentData = {
                    id: string;
                    type: App.Domain.Verification.Enums.DocumentType;
                    expiresOn: string | null;
                    uploadedAt: string;
                    state: string | null;
                    fileUrl: string | null;
                    sharedWith: string[];
                };
                export type ProfileCompletionData = {
                    percent: number;
                    steps: App.Domain.Worker.Data.ProfileStepData[];
                    next: string | null;
                };
                export type ProfileOptionsData = {
                    occupations: App.Domain.Taxonomy.Data.OccupationOptionData[];
                    skills: App.Domain.Taxonomy.Data.TaxonomyOptionData[];
                    countries: App.Domain.Config.Data.CountryOptionData[];
                    destinations: App.Domain.Config.Data.CountryOptionData[];
                };
                export type ProfileStepData = {
                    key: string;
                    done: boolean;
                };
                export type SkillShowData = {
                    id: string;
                    status: App.Domain.Worker.Enums.ModerationStatus;
                    durationMs: number | null;
                    visibility: App.Domain.Worker.Enums.ProfileVisibility;
                    skills: string[];
                    skillSlugs: string[];
                    reasonCode: string | null;
                    videoUrl: string | null;
                    posterUrl: string | null;
                    createdAt: string | null;
                };
                export type SkillShowReviewData = {
                    skillShow: App.Domain.Worker.Data.SkillShowData;
                    workerName: string;
                    reference: string;
                    occupation: string | null;
                };
                export type VoiceAnswerData = {
                    id: string;
                    question: App.Domain.Worker.Enums.VoiceQuestion;
                    status: App.Domain.Worker.Enums.VoiceAnswerStatus;
                    typed: boolean;
                    transcript: string | null;
                    fields: App.Domain.Worker.Data.VoiceFieldsData | null;
                    errorCode: string | null;
                    confirmedAt: string | null;
                };
                export type VoiceCountryData = {
                    code: string;
                    years: number | null;
                };
                export type VoiceFieldsData = {
                    occupation: string | null;
                    specialization: string | null;
                    yearsExperience: number | null;
                    countries: App.Domain.Worker.Data.VoiceCountryData[] | null;
                    skills: string[] | null;
                    passportStatus: string | null;
                    passportValidUntilYear: number | null;
                    availability: string | null;
                    destinations: string[] | null;
                };
                export type VoiceProgressData = {
                    question: App.Domain.Worker.Enums.VoiceQuestion;
                    confirmed: boolean;
                };
                export type WorkerExperienceData = {
                    id: string;
                    countryCode: string;
                    countryName: string;
                    role: string;
                    employerName: string | null;
                    years: number | null;
                    startedOn: string | null;
                    endedOn: string | null;
                    description: string | null;
                };
                export type WorkerLanguageData = {
                    code: string;
                    name: string;
                    proficiency: App.Domain.Worker.Enums.LanguageProficiency;
                };
                export type WorkerPassportData = {
                    reference: string;
                    name: string;
                    headline: string | null;
                    photoUrl: string | null;
                    currentCountry: App.Domain.Config.Data.CountryOptionData | null;
                    nationality: string;
                    occupation: App.Domain.Taxonomy.Data.TaxonomyOptionData | null;
                    specialization: App.Domain.Taxonomy.Data.TaxonomyOptionData | null;
                    yearsExperience: number | null;
                    passportStatus: App.Domain.Worker.Enums.PassportStatus | null;
                    passportValidUntilYear: number | null;
                    availability: App.Domain.Worker.Enums.Availability | null;
                    salaryExpectation: App.Support.Data.MoneyData | null;
                    salaryPeriod: App.Domain.Jobs.Enums.SalaryPeriod | null;
                    visibility: App.Domain.Worker.Enums.ProfileVisibility;
                    experiences: App.Domain.Worker.Data.WorkerExperienceData[];
                    skills: App.Domain.Worker.Data.WorkerSkillData[];
                    languages: App.Domain.Worker.Data.WorkerLanguageData[];
                    destinations: App.Domain.Config.Data.CountryOptionData[];
                    skillShow: App.Domain.Worker.Data.SkillShowData | null;
                    phoneVerified: boolean;
                    evidence: App.Domain.Worker.Data.EvidenceSummaryData[];
                    completion: App.Domain.Worker.Data.ProfileCompletionData;
                };
                export type WorkerSkillData = {
                    id: string;
                    slug: string;
                    name: string;
                    source: App.Domain.Worker.Enums.SkillSource;
                };
            }
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
                export type SkillShowRejectionReason =
                    | 'not_a_demonstration'
                    | 'unsafe_work'
                    | 'personal_information'
                    | 'poor_quality'
                    | 'inappropriate';
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
