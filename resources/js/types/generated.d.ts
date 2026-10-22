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
                    mobilityCaseId: string | null;
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
                export type CandidateListItemData = {
                    applicant: App.Domain.Applications.Data.ApplicantListItemData;
                    jobTitle: string;
                    jobCode: string;
                    destinationCountryCode: string;
                    mobilityCaseId: string | null;
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
                export type JobInvitationData = {
                    id: string;
                    job: App.Domain.Jobs.Data.JobListingData;
                    organization: string;
                    note: string | null;
                    status: App.Domain.Applications.Enums.InvitationStatus;
                    sentAt: string;
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
                    mobilityCaseId: string | null;
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
        namespace Billing {
            namespace Enums {
                export type BillingPeriod = 'monthly' | 'yearly' | 'one_off';
                export type CreditReason =
                    'plan_grant' | 'purchase' | 'adjustment' | 'job_published' | 'expiry';
                export type PaymentMethod = 'bank_transfer' | 'card' | 'cash' | 'other';
            }
        }
        namespace Config {
            namespace Data {
                export type CountryConfigData = {
                    code: string;
                    name: string;
                    currency: string;
                    source: boolean;
                    destination: boolean;
                    legalReviewedAt: string | null;
                    legalReviewedBy: string | null;
                    rules: Record<string, any> | null;
                };
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
        namespace Crm {
            namespace Data {
                export type CampaignData = {
                    id: string;
                    code: string;
                    name: string;
                    channel: App.Domain.Crm.Enums.CampaignChannel;
                    sourceCountryCode: string | null;
                    partnerId: string | null;
                    partner: string | null;
                    landing: App.Domain.Crm.Enums.CampaignLanding;
                    status: App.Support.Data.StatusData;
                    startsOn: string;
                    endsOn: string | null;
                    link: string;
                    funnel: App.Domain.Crm.Data.FunnelData;
                };
                export type ContactAttemptData = {
                    id: string;
                    channel: App.Domain.Crm.Enums.ContactChannel;
                    outcome: App.Domain.Crm.Enums.ContactOutcome;
                    note: string | null;
                    staff: string | null;
                    attemptedAt: string;
                };
                export type FunnelData = {
                    visitors: number;
                    leads: number;
                    registrations: number;
                    profilesComplete: number;
                    qualified: number;
                    applied: number;
                    selected: number;
                    deployed: number;
                };
                export type LeadData = {
                    id: string;
                    name: string;
                    signedUp: boolean;
                    passportReference: string | null;
                    phone: string | null;
                    optedOut: boolean;
                    source: App.Domain.Crm.Enums.LeadSource;
                    campaign: string | null;
                    partner: string | null;
                    countryCode: string | null;
                    assigneeId: string | null;
                    followUpStatus: App.Domain.Crm.Enums.FollowUpStatus;
                    followUp: App.Support.Data.StatusData;
                    stage: App.Support.Data.StatusData;
                    nextFollowUpAt: string | null;
                    lastContactedAt: string | null;
                    createdAt: string;
                    progress: {
                        stage: App.Domain.Crm.Enums.ConversionStage;
                        reached: boolean;
                    }[];
                    attempts: App.Domain.Crm.Data.ContactAttemptData[];
                };
                export type LeadListItemData = {
                    id: string;
                    name: string;
                    signedUp: boolean;
                    source: App.Domain.Crm.Enums.LeadSource;
                    campaign: string | null;
                    countryCode: string | null;
                    followUp: App.Support.Data.StatusData;
                    stage: App.Support.Data.StatusData;
                    assignee: string | null;
                    nextFollowUpAt: string | null;
                    overdue: boolean;
                    lastContactedAt: string | null;
                    createdAt: string;
                };
            }
            namespace Enums {
                export type CampaignChannel =
                    | 'social'
                    | 'job_fair'
                    | 'partner'
                    | 'institute'
                    | 'radio_tv'
                    | 'sms_whatsapp'
                    | 'referral'
                    | 'other';
                export type CampaignLanding = 'join' | 'jobs';
                export type ContactChannel =
                    'phone_call' | 'whatsapp' | 'sms' | 'email' | 'in_person';
                export type ContactOutcome =
                    'reached' | 'no_answer' | 'wrong_number' | 'call_back' | 'not_interested';
                export type ConversionStage =
                    | 'prospect'
                    | 'registered'
                    | 'profile_complete'
                    | 'qualified'
                    | 'applied'
                    | 'selected'
                    | 'deployed';
                export type FollowUpStatus =
                    | 'new'
                    | 'contacted'
                    | 'no_answer'
                    | 'call_back'
                    | 'not_interested'
                    | 'do_not_contact';
                export type LeadSource =
                    'campaign' | 'institute' | 'direct' | 'walk_in' | 'call_centre' | 'referral';
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
                    | 'mandates.confirm'
                    | 'mobility.manage'
                    | 'compliance.review'
                    | 'courses.manage'
                    | 'cohorts.manage'
                    | 'graduates.invite'
                    | 'attestations.issue'
                    | 'assessments.record'
                    | 'placements.view'
                    | 'projects.manage'
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
        namespace Institute {
            namespace Data {
                export type CohortData = {
                    summary: App.Domain.Institute.Data.CohortSummaryData;
                    courseId: string;
                    inviteCode: string;
                    inviteUrl: string;
                    whatsappText: string;
                    qrSvg: string;
                    skillShows: number;
                    members: App.Domain.Institute.Data.CohortMemberData[];
                };
                export type CohortInviteData = {
                    code: string;
                    institute: string;
                    instituteVerified: boolean;
                    course: string;
                    occupation: string | null;
                    cohort: string;
                    startsOn: string | null;
                    endsOn: string | null;
                    open: boolean;
                };
                export type CohortMemberData = {
                    id: string;
                    cohortId: string;
                    cohortName: string;
                    courseTitle: string;
                    cohortEndsOn: string | null;
                    name: string;
                    status: App.Support.Data.StatusData;
                    source: string;
                    rosterPhone: string | null;
                    passportReference: string | null;
                    respondedAt: string | null;
                    sharesProgress: boolean;
                    hasSkillShow: boolean;
                    attestation: App.Domain.Institute.Data.IssuedRecordData | null;
                    assessment: App.Domain.Institute.Data.IssuedRecordData | null;
                };
                export type CohortSummaryData = {
                    id: string;
                    name: string;
                    courseTitle: string;
                    startsOn: string | null;
                    endsOn: string | null;
                    status: App.Support.Data.StatusData;
                    joined: number;
                    invited: number;
                };
                export type CourseData = {
                    id: string;
                    title: string;
                    occupationId: string | null;
                    occupation: string | null;
                    durationWeeks: number | null;
                    description: string | null;
                    cohorts: App.Domain.Institute.Data.CohortSummaryData[];
                };
                export type InstituteCountsData = {
                    openCohorts: number;
                    graduates: number;
                    rosterWaiting: number;
                    skillShows: number;
                    attested: number;
                    assessmentsPassed: number;
                    sharingProgress: number;
                };
                export type IssuedRecordData = {
                    id: string;
                    title: string;
                    status: App.Support.Data.StatusData;
                    date: string;
                    score: number | null;
                    withdrawn: boolean;
                    withdrawnReason: string | null;
                };
                export type PlacementData = {
                    memberId: string;
                    name: string;
                    passportReference: string;
                    cohort: string;
                    course: string;
                    interest: {
                        organization: string;
                        job: string;
                        sentAt: string;
                    }[];
                    applications: {
                        job: string;
                        jobCode: string;
                        employer: string;
                        status: App.Support.Data.StatusData;
                        appliedAt: string;
                        caseStatus: App.Support.Data.StatusData | null;
                    }[];
                    placed: boolean;
                };
                export type PoolMemberData = {
                    profileId: string;
                    name: string;
                    passportReference: string;
                    occupation: string | null;
                    visibleToEmployers: boolean;
                    hasSkillShow: boolean;
                    attested: boolean;
                };
                export type TalentPoolData = {
                    id: string;
                    name: string;
                    description: string | null;
                    occupationId: string | null;
                    occupation: string | null;
                    institute: string;
                    instituteVerified: boolean;
                    publishedAt: string | null;
                    members: number;
                    visibleMembers: number;
                };
                export type WorkerCohortData = {
                    id: string;
                    institute: string;
                    course: string;
                    cohort: string;
                    sharesProgress: boolean;
                    joinedAt: string | null;
                };
            }
            namespace Enums {
                export type AssessmentResult = 'pass' | 'fail';
                export type CohortMemberSource = 'roster' | 'link';
                export type CohortMemberStatus = 'invited' | 'accepted' | 'declined' | 'left';
                export type CohortStatus = 'open' | 'closed';
                export type IssuedRecordStatus = 'issued' | 'withdrawn';
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
                    jobMandateId: string | null;
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
                    mandates: App.Domain.Jobs.Data.MandateOptionData[];
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
                    employer: string | null;
                    mandateReference: string | null;
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
                export type MandateCoverageData = {
                    mandate: App.Domain.Jobs.Data.MandateData;
                    placesLeft: number;
                    liveJobs: number;
                    expiringSoon: boolean;
                };
                export type MandateData = {
                    id: string;
                    reference: string;
                    agency: string;
                    agencyLicence: string | null;
                    employer: string;
                    status: App.Support.Data.StatusData;
                    usable: boolean;
                    occupations: string[];
                    destinationCountryCode: string | null;
                    headcount: number;
                    validFrom: string | null;
                    validUntil: string | null;
                    employerNote: string | null;
                    employerRespondedAt: string | null;
                    jobs: number;
                    evidence: {
                        name: string;
                        url: string | null;
                    }[];
                    lastDecision: App.Domain.Verification.Data.ReviewDecisionData | null;
                    createdAt: string | null;
                };
                export type MandateOptionData = {
                    id: string;
                    reference: string;
                    employer: string;
                    occupationIds: string[];
                    destinationCountryCode: string | null;
                    headcountLeft: number;
                    validUntil: string | null;
                    usable: boolean;
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
                export type RecruitmentProjectData = {
                    summary: App.Domain.Jobs.Data.RecruitmentProjectListItemData;
                    stages: {
                        key: string;
                        count: number;
                    }[];
                    jobs: {
                        id: string;
                        code: string;
                        title: string;
                        status: App.Support.Data.StatusData;
                        headcount: number;
                        applicants: number;
                        joined: number;
                    }[];
                    team: {
                        id: string;
                        name: string;
                    }[];
                };
                export type RecruitmentProjectListItemData = {
                    id: string;
                    reference: string;
                    name: string;
                    status: App.Support.Data.StatusData;
                    targetHeadcount: number;
                    joined: number;
                    jobs: number;
                    destinationCountryCode: string | null;
                    sourceCountryCodes: string[];
                    targetDate: string | null;
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
                    | 'awaiting_employer'
                    | 'declined'
                    | 'pending'
                    | 'verified'
                    | 'rejected'
                    | 'expired'
                    | 'revoked';
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
                export type PreferenceRowData = {
                    event: App.Domain.Messaging.Enums.NotificationEvent;
                    channels: Record<string, boolean>;
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
                    | 'case_opened'
                    | 'case_updated'
                    | 'case_closed'
                    | 'case_document'
                    | 'institute_record'
                    | 'evidence_reviewed'
                    | 'job_suspended'
                    | 'account_suspended'
                    | 'report_outcome'
                    | 'appeal_decided'
                    | 'new_applicants'
                    | 'application_answered'
                    | 'organization_message'
                    | 'mandate_requested'
                    | 'mandate_answered'
                    | 'mandate_reviewed'
                    | 'case_document_answered'
                    | 'job_paused_mandate'
                    | 'graduate_joined'
                    | 'organization_suspended';
            }
        }
        namespace Mobility {
            namespace Data {
                export type CaseCountsData = {
                    open: number;
                    documentsToCheck: number;
                    overdue: number;
                    onHold: number;
                };
                export type CaseDocumentData = {
                    id: string;
                    type: App.Domain.Verification.Enums.DocumentType;
                    title: string;
                    status: App.Support.Data.StatusData;
                    providedBy: App.Domain.Mobility.Enums.DocumentProvider;
                    employerCanView: boolean;
                    visibleTo: string[];
                    revoked: boolean;
                    fileName: string | null;
                    fileUrl: string | null;
                    sharedAt: string | null;
                    decidedAt: string | null;
                    dueOn: string | null;
                    returnReason: App.Domain.Mobility.Enums.CaseDocumentReturnReason | null;
                    note: string | null;
                };
                export type CaseEventData = {
                    id: string;
                    action: App.Domain.Mobility.Enums.CaseEventAction;
                    stage: string | null;
                    document: string | null;
                    actor: string | null;
                    note: string | null;
                    at: string;
                };
                export type CaseListItemData = {
                    id: string;
                    reference: string;
                    workerName: string;
                    jobTitle: string;
                    jobCode: string;
                    destinationCountryCode: string;
                    status: App.Support.Data.StatusData;
                    stage: string | null;
                    onHold: boolean;
                    progress: string[];
                    dueOn: string | null;
                    overdue: boolean;
                    documentsToCheck: number;
                    managedBy: string;
                    viewOnly: boolean;
                };
                export type CaseStageData = {
                    id: string;
                    key: string;
                    label: string;
                    status: string;
                    date: string | null;
                    dueOn: string | null;
                    nextAction: string | null;
                    blockedReason: string | null;
                };
                export type MobilityCaseData = {
                    id: string;
                    reference: string;
                    status: App.Support.Data.StatusData;
                    closedReason: App.Domain.Mobility.Enums.CaseClosedReason | null;
                    closedAt: string | null;
                    applicationId: string;
                    jobTitle: string;
                    jobCode: string;
                    destinationCountryCode: string;
                    destination: string;
                    employer: string;
                    agency: string | null;
                    managedBy: string;
                    handledBy: string;
                    workerName: string | null;
                    current: App.Domain.Mobility.Data.CaseStageData | null;
                    stages: App.Domain.Mobility.Data.CaseStageData[];
                    events: App.Domain.Mobility.Data.CaseEventData[];
                    openedAt: string;
                };
            }
            namespace Enums {
                export type CaseClosedReason =
                    | 'joined'
                    | 'worker_withdrew'
                    | 'employer_cancelled'
                    | 'visa_refused'
                    | 'medical_unfit'
                    | 'other';
                export type CaseDocumentReturnReason =
                    | 'unreadable'
                    | 'wrong_document'
                    | 'expired'
                    | 'incomplete'
                    | 'details_mismatch'
                    | 'other';
                export type CaseDocumentStatus = 'requested' | 'uploaded' | 'accepted' | 'returned';
                export type CaseEventAction =
                    | 'opened'
                    | 'stage_started'
                    | 'stage_completed'
                    | 'stage_blocked'
                    | 'stage_skipped'
                    | 'stage_resumed'
                    | 'next_action_set'
                    | 'document_requested'
                    | 'document_uploaded'
                    | 'document_accepted'
                    | 'document_returned'
                    | 'document_revoked'
                    | 'contract_confirmed'
                    | 'closed';
                export type DocumentProvider = 'worker' | 'organization';
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
                export type TaxonomyItemData = {
                    id: string;
                    type: string;
                    slug: string;
                    names: Record<string, string>;
                    usage: number;
                    children: App.Domain.Taxonomy.Data.TaxonomyItemData[];
                };
                export type TaxonomyOptionData = {
                    id: string;
                    slug: string;
                    name: string;
                };
            }
        }
        namespace Trust {
            namespace Data {
                export type AdminOverviewData = {
                    queues: {
                        key: string;
                        count: number;
                    }[];
                    marketplace: Record<string, number>;
                    corridorsPendingLegalReview: number;
                };
                export type AppealData = {
                    id: string;
                    status: App.Support.Data.StatusData;
                    statement: string;
                    appellant: string | null;
                    decisionNote: string | null;
                    decider: string | null;
                    submittedAt: string;
                    decidedAt: string | null;
                };
                export type AuditEntryData = {
                    id: string | number;
                    at: string;
                    area: string | null;
                    event: string;
                    actor: string | null;
                    actorEmail: string | null;
                    subjectType: string | null;
                    subjectId: string | null;
                    properties: string;
                };
                export type ReportData = {
                    id: string;
                    category: App.Domain.Trust.Enums.ReportCategory;
                    description: string;
                    status: App.Support.Data.StatusData;
                    about: string;
                    subject: string;
                    excerpt: string | null;
                    reporter: string | null;
                    createdAt: string;
                    caseId: string | null;
                    caseReference: string | null;
                    outcome: string | null;
                    reasonCode: string | null;
                };
                export type TrustActionData = {
                    id: string;
                    action: App.Domain.Trust.Enums.TrustActionType;
                    reason: App.Domain.Trust.Enums.TrustReason;
                    subject: string;
                    note: string | null;
                    actor: string | null;
                    actorId: string | null;
                    at: string;
                    appeals: App.Domain.Trust.Data.AppealData[];
                };
                export type TrustCaseData = {
                    summary: App.Domain.Trust.Data.TrustCaseListItemData;
                    summaryText: string;
                    assigneeId: string | null;
                    outcomeCode: string | null;
                    closedAt: string | null;
                    reports: App.Domain.Trust.Data.ReportData[];
                    notes: {
                        id: string;
                        body: string;
                        author: string | null;
                        at: string;
                        files: {
                            name: string;
                            url: string | null;
                        }[];
                    }[];
                    actions: App.Domain.Trust.Data.TrustActionData[];
                };
                export type TrustCaseListItemData = {
                    id: string;
                    reference: string;
                    category: App.Domain.Trust.Enums.ReportCategory;
                    status: App.Support.Data.StatusData;
                    priority: App.Support.Data.StatusData;
                    subjectType: string;
                    subject: string;
                    subjectSuspended: boolean;
                    assignee: string | null;
                    reports: number;
                    openedAt: string;
                };
            }
            namespace Enums {
                export type AppealStatus = 'submitted' | 'upheld' | 'overturned';
                export type CasePriority = 'low' | 'normal' | 'high' | 'urgent';
                export type ReportCategory =
                    | 'fake_job'
                    | 'unauthorized_payment'
                    | 'misleading_salary'
                    | 'impersonation'
                    | 'harassment'
                    | 'fake_credential'
                    | 'other';
                export type ReportOutcome = 'action_taken' | 'no_action';
                export type TrustActionType = 'suspend' | 'restore';
                export type TrustReason =
                    | 'fake_job'
                    | 'unauthorized_payment'
                    | 'misleading_conditions'
                    | 'impersonation'
                    | 'harassment'
                    | 'fake_credentials'
                    | 'repeated_breaches'
                    | 'no_evidence'
                    | 'not_a_breach'
                    | 'duplicate'
                    | 'resolved'
                    | 'appeal_upheld'
                    | 'appeal_overturned';
            }
        }
        namespace Verification {
            namespace Data {
                export type EvidenceReviewData = {
                    id: string;
                    type: App.Domain.Verification.Enums.EvidenceType;
                    title: string | null;
                    state: App.Support.Data.StatusData;
                    workerName: string;
                    passportReference: string;
                    uploadedAt: string;
                    expiresOn: string | null;
                    reviewer: string | null;
                    fileUrl: string | null;
                    fileName: string | null;
                    history: App.Domain.Verification.Data.ReviewDecisionData[];
                };
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
                    | 'mandate_not_signed'
                    | 'mandate_scope_mismatch'
                    | 'licence_expired'
                    | 'not_genuine'
                    | 'wrong_document'
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
                    issuer: string | null;
                    issuedOn: string | null;
                    hidden: boolean;
                    hideable: boolean;
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
