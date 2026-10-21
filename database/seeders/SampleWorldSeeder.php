<?php

namespace Database\Seeders;

use App\Domain\Applications\Data\ProfileSnapshotData;
use App\Domain\Applications\Data\ShareableItemData;
use App\Domain\Applications\Enums\ApplicationConsentItem;
use App\Domain\Applications\Enums\ConsentPurpose;
use App\Domain\Applications\Enums\InterviewMode;
use App\Domain\Applications\Enums\InterviewOutcome;
use App\Domain\Applications\Enums\InvitationStatus;
use App\Domain\Applications\Enums\OfferStatus;
use App\Domain\Applications\Enums\RejectionReason;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\JobInvitation;
use App\Domain\Applications\States\Application\Accepted;
use App\Domain\Applications\States\Application\ApplicationState;
use App\Domain\Applications\States\Application\Applied;
use App\Domain\Applications\States\Application\Interview;
use App\Domain\Applications\States\Application\Offer as OfferStage;
use App\Domain\Applications\States\Application\Rejected;
use App\Domain\Applications\States\Application\Reviewed;
use App\Domain\Applications\States\Application\Shortlisted;
use App\Domain\Applications\Support\ShareableItems;
use App\Domain\Config\Enums\CountryRuleKey;
use App\Domain\Config\Models\CountryRule;
use App\Domain\Identity\Enums\OrganizationRole;
use App\Domain\Identity\Enums\StaffRole;
use App\Domain\Identity\Enums\UserType;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Support\TeamContext;
use App\Domain\Jobs\Enums\BenefitProvision;
use App\Domain\Jobs\Enums\CostPayer;
use App\Domain\Jobs\Enums\JobCostCategory;
use App\Domain\Jobs\Enums\MandateStatus;
use App\Domain\Jobs\Enums\SalaryPeriod;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Draft;
use App\Domain\Jobs\States\Job\Published;
use App\Domain\Jobs\States\Job\UnderReview;
use App\Domain\Messaging\Enums\DeliveryChannel;
use App\Domain\Messaging\Enums\DeliveryStatus;
use App\Domain\Messaging\Enums\MessageSide;
use App\Domain\Messaging\Enums\NotificationEvent;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\ConversationRead;
use App\Domain\Messaging\Models\OutboundMessage;
use App\Domain\Mobility\Enums\CaseClosedReason;
use App\Domain\Mobility\Enums\CaseDocumentStatus;
use App\Domain\Mobility\Enums\CaseEventAction;
use App\Domain\Mobility\Enums\DocumentProvider;
use App\Domain\Mobility\Enums\MobilityStageStatus;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Mobility\States\MobilityCase\Completed;
use App\Domain\Mobility\States\MobilityCase\Open as OpenCase;
use App\Domain\Mobility\Support\CaseChecklist;
use App\Domain\Organization\Enums\OrganizationType;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\States\Verification\Pending as PendingOrganization;
use App\Domain\Organization\States\Verification\Verified;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Taxonomy\Models\Specialization;
use App\Domain\Trust\Enums\ReportCategory;
use App\Domain\Trust\Models\Report;
use App\Domain\Trust\States\Report\Open as OpenReport;
use App\Domain\Verification\Enums\DocumentType;
use App\Domain\Verification\Enums\EvidenceType;
use App\Domain\Verification\Enums\VerificationDecision;
use App\Domain\Verification\Enums\VerificationType;
use App\Domain\Verification\Models\Document;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Verification\States\Evidence\EvidenceState;
use App\Domain\Verification\States\Evidence\IssuerVerified;
use App\Domain\Verification\States\Evidence\UploadedUnreviewed;
use App\Domain\Verification\States\Evidence\VerificationPending;
use App\Domain\Verification\States\VerificationRequest\Approved;
use App\Domain\Verification\States\VerificationRequest\InReview;
use App\Domain\Verification\States\VerificationRequest\Open as OpenRequest;
use App\Domain\Worker\Enums\Availability;
use App\Domain\Worker\Enums\LanguageProficiency;
use App\Domain\Worker\Enums\ModerationStatus;
use App\Domain\Worker\Enums\PassportStatus;
use App\Domain\Worker\Enums\ProfileVisibility;
use App\Domain\Worker\Enums\SkillSource;
use App\Domain\Worker\Enums\VoiceAnswerStatus;
use App\Domain\Worker\Enums\VoiceQuestion;
use App\Domain\Worker\Models\SkillShow;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Fictional sample world matching prototype-v1. Never runs in production. All
 * organizations and people are invented; amounts are placeholders, not live vacancies.
 */
class SampleWorldSeeder extends Seeder
{
    public const PASSWORD = 'password';

    /** A one-page blank PDF labelled as sample data. */
    private const SAMPLE_PDF = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 300 144]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj\n4 0 obj<</Length 52>>stream\nBT /F1 14 Tf 20 70 Td (SAMPLE PASSPORT SCAN) Tj ET\nendstream endobj\n5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";

    /** @var array<string, Organization> */
    private array $orgs = [];

    /** @var array<string, JobPosting> */
    private array $jobs = [];

    /** @var array<string, WorkerProfile> */
    private array $workers = [];

    /** @var array<string, User> Organization members by email. */
    private array $orgUsers = [];

    private User $verificationOfficer;

    public function run(): void
    {
        $this->seedStaff();
        $this->seedOrganizations();
        $this->seedMandatesAndJobs();
        $this->seedWorkers();
        $this->seedRahimPassport();
        $this->seedApplicationsAndCases();
        $this->seedQueues();
    }

    private function seedStaff(): void
    {
        $this->verificationOfficer = $this->user('S. Rahman', 's.rahman@blueskills.test', UserType::Staff, 'Asia/Dubai');
        $this->verificationOfficer->assignStaffRole(StaffRole::VerificationOfficer);

        $trust = $this->user('L. Haddad', 'l.haddad@blueskills.test', UserType::Staff, 'Asia/Dubai');
        $trust->assignStaffRole(StaffRole::TrustOfficer);
    }

    private function seedOrganizations(): void
    {
        $definitions = [
            'abc' => [OrganizationType::Employer, 'ABC Contracting LLC', 'ABC Contracting', 'AE', 'Dubai', ['registration_number' => 'DED-1023456']],
            'northstar' => [OrganizationType::Agency, 'Northstar Manpower Ltd.', 'Northstar Manpower', 'BD', 'Dhaka', [
                'registration_number' => 'RJSC-C-88231',
                'licence_number' => 'RA-2026-0418',
                'licence_expires_on' => '2027-03-31',
                'licence_countries' => ['AE', 'SA'],
            ]],
            'meghna' => [OrganizationType::Institute, 'Meghna Technical Training Centre', 'Meghna Technical Training Centre', 'BD', 'Narayanganj', ['registration_number' => 'BTEB-55120']],
            'xyz' => [OrganizationType::Employer, 'XYZ Logistics Sp. z o.o.', 'XYZ Logistics', 'PL', 'Poznań', ['registration_number' => 'KRS-0000900123']],
            'global' => [OrganizationType::Employer, 'Global Facilities KK', 'Global Facilities', 'JP', 'Toyota, Aichi', ['registration_number' => 'JCN-9180001234567']],
            'alnoor' => [OrganizationType::Employer, 'Al Noor Facilities Co.', 'Al Noor Facilities', 'SA', 'Riyadh', ['registration_number' => 'CR-1010998877']],
            'moravia' => [OrganizationType::Employer, 'Moravia Steelworks s.r.o.', 'Moravia Steelworks', 'CZ', 'Ostrava', ['registration_number' => 'ICO-12345678']],
        ];

        foreach ($definitions as $key => [$type, $legal, $display, $country, $city, $extra]) {
            $this->orgs[$key] = Organization::query()->create([
                'type' => $type,
                'legal_name' => $legal,
                'display_name' => $display,
                'country_code' => $country,
                'city' => $city,
                'verification_status' => Verified::class,
                'verified_at' => now()->subMonths(2),
                'data_region' => config('blueskills.default_data_region'),
                ...$extra,
            ]);

            $this->orgs[$key]->verificationRequests()->create([
                'type' => $type === OrganizationType::Agency ? VerificationType::AgencyLicence : ($type === OrganizationType::Institute ? VerificationType::InstituteAccreditation : VerificationType::Organization),
                'status' => Approved::class,
                'decided_by' => $this->verificationOfficer->id,
                'decision' => VerificationDecision::Approved,
                'decided_at' => now()->subMonths(2),
            ]);
        }

        $members = [
            ['abc', 'Omar Farouk', 'owner@abc-contracting.test', 'Managing Director', OrganizationRole::Owner, 'Asia/Dubai'],
            ['abc', 'Sara K.', 'sara@abc-contracting.test', 'HR Admin', OrganizationRole::HrAdmin, 'Asia/Dubai'],
            ['northstar', 'Farhana Akter', 'owner@northstar.test', 'Director', OrganizationRole::AgencyOwner, 'Asia/Dhaka'],
            ['northstar', 'Imran H.', 'imran@northstar.test', 'Case Officer', OrganizationRole::CaseOfficer, 'Asia/Dhaka'],
            ['meghna', 'Abdul Karim', 'principal@meghna.test', 'Principal', OrganizationRole::InstituteOwner, 'Asia/Dhaka'],
            ['meghna', 'Nusrat J.', 'nusrat@meghna.test', 'Placement Officer', OrganizationRole::PlacementOfficer, 'Asia/Dhaka'],
            ['xyz', 'Katarzyna Nowak', 'hr@xyz-logistics.test', 'HR Manager', OrganizationRole::Owner, 'Europe/Warsaw'],
            ['global', 'Kenji Sato', 'hr@global-facilities.test', 'HR Manager', OrganizationRole::Owner, 'Asia/Tokyo'],
            ['alnoor', 'Faisal Al-Harbi', 'hr@alnoor.test', 'HR Manager', OrganizationRole::Owner, 'Asia/Riyadh'],
            ['moravia', 'Petr Dvořák', 'hr@moravia.test', 'HR Manager', OrganizationRole::Owner, 'Europe/Prague'],
        ];

        foreach ($members as [$orgKey, $name, $email, $title, $role, $timezone]) {
            $user = $this->orgUsers[$email] = $this->user($name, $email, UserType::Org, $timezone);
            $org = $this->orgs[$orgKey];
            $org->users()->attach($user->id, ['title' => $title, 'joined_at' => now()->subMonths(2)]);
            TeamContext::run($org->id, fn () => $user->unsetRelation('roles')->assignRole($role->value));
        }
    }

    private function seedMandatesAndJobs(): void
    {
        $md1107 = JobMandate::query()->create([
            'employer_organization_id' => $this->orgs['abc']->id,
            'agency_organization_id' => $this->orgs['northstar']->id,
            'reference' => 'MD-1107',
            'occupation_ids' => [Occupation::query()->where('slug', 'electrician')->value('id')],
            'destination_country_code' => 'AE',
            // 200 places go to UAE-EL-0001; the rest let the agency post a second job (6c).
            'headcount' => 300,
            'valid_from' => '2026-09-01',
            'valid_until' => '2027-03-31',
            'status' => MandateStatus::Verified,
        ]);

        $md1121 = JobMandate::query()->create([
            'employer_organization_id' => $this->orgs['alnoor']->id,
            'agency_organization_id' => $this->orgs['northstar']->id,
            'reference' => 'MD-1121',
            'occupation_ids' => [Occupation::query()->where('slug', 'hvac-technician')->value('id')],
            'destination_country_code' => 'SA',
            'headcount' => 40,
            'valid_from' => '2026-10-01',
            'valid_until' => '2027-04-30',
            'status' => MandateStatus::Pending,
        ]);
        $md1121->verificationRequests()->create(['type' => VerificationType::JobMandate, 'status' => OpenRequest::class]);

        $this->jobs['UAE-EL-0001'] = $this->job([
            'organization_id' => $this->orgs['northstar']->id,
            'employer_organization_id' => $this->orgs['abc']->id,
            'agency_organization_id' => $this->orgs['northstar']->id,
            'job_mandate_id' => $md1107->id,
            'public_code' => 'UAE-EL-0001',
            'title' => 'Building Electrician',
            'occupation' => 'electrician',
            'min_experience_years' => 5,
            'language_requirements' => [['code' => 'en', 'level' => 'basic'], ['code' => 'hi', 'level' => 'basic'], ['code' => 'ur', 'level' => 'basic']],
            'destination_country_code' => 'AE',
            'destination_city' => 'Dubai',
            'headcount' => 200,
            'salary_amount_minor' => 2200_00,
            'salary_currency' => 'AED',
            'hours_per_day' => 8,
            'days_per_week' => 6,
            'overtime_terms' => 'Paid at 1.25× basic hourly rate',
            'contract_months' => 24,
            'accommodation' => BenefitProvision::Provided, 'accommodation_details' => 'Shared, 4 per room',
            'food' => BenefitProvision::Allowance, 'food_details' => 'AED 300 monthly allowance',
            'transport' => BenefitProvision::Provided, 'transport_details' => 'Provided to site',
            'medical' => BenefitProvision::Provided, 'medical_details' => 'Employer health insurance',
            'airfare' => BenefitProvision::Provided, 'airfare_details' => 'Employer pays (both ways)',
            'permit_payer' => CostPayer::Employer,
            'visa_payer' => CostPayer::Employer,
            'joining_from' => '2027-01-01',
            'joining_to' => '2027-02-28',
            'deadline' => '2026-11-30',
            'status' => Published::class,
            'published_at' => '2026-10-01 09:00:00',
        ], ['industrial-wiring', 'db-installation', 'cable-termination'], []);

        $this->jobs['PL-WH-0014'] = $this->job([
            'organization_id' => $this->orgs['xyz']->id,
            'employer_organization_id' => $this->orgs['xyz']->id,
            'public_code' => 'PL-WH-0014',
            'title' => 'Warehouse Professional',
            'occupation' => 'warehouse-operative',
            'min_experience_years' => 1,
            'language_requirements' => [['code' => 'en', 'level' => 'basic']],
            'destination_country_code' => 'PL',
            'destination_city' => 'Poznań',
            'headcount' => 75,
            'salary_amount_minor' => 5200_00,
            'salary_currency' => 'PLN',
            'hours_per_day' => 8,
            'days_per_week' => 5,
            'overtime_terms' => 'Paid per Polish labour code',
            'contract_months' => 12,
            // accommodation, food and airfare are deliberately not disclosed
            'transport' => BenefitProvision::Provided, 'transport_details' => 'Shift bus from city centre',
            'medical' => BenefitProvision::Provided, 'medical_details' => 'National health insurance',
            'permit_payer' => CostPayer::Employer,
            'visa_payer' => CostPayer::Worker,
            'deadline' => '2026-12-15',
            // a material change sent this job back to review (product rule 5)
            'status' => UnderReview::class,
            'published_at' => '2026-09-20 09:00:00',
        ], ['forklift-udt', 'picking-packing', 'scanner-use'], [
            [JobCostCategory::Visa, 'Visa application fee', 80_00, 'EUR', CostPayer::Worker],
            [JobCostCategory::Medical, 'Medical check', 60_00, 'EUR', CostPayer::Worker],
        ]);

        $this->jobs['JP-IT-0007'] = $this->job([
            'organization_id' => $this->orgs['global']->id,
            'employer_organization_id' => $this->orgs['global']->id,
            'public_code' => 'JP-IT-0007',
            'title' => 'Industrial Technician',
            'occupation' => 'electrical-technician',
            'min_experience_years' => 3,
            'language_requirements' => [['code' => 'ja', 'level' => 'JLPT N4']],
            'destination_country_code' => 'JP',
            'destination_city' => 'Toyota, Aichi',
            'headcount' => 40,
            // JPY has no minor unit, so the minor amount equals the yen amount
            'salary_amount_minor' => 230000,
            'salary_currency' => 'JPY',
            'hours_per_day' => 8,
            'days_per_week' => 5,
            'overtime_terms' => 'Paid at 1.25× per Japanese labour law',
            'contract_months' => 36,
            'accommodation' => BenefitProvision::Deducted, 'accommodation_details' => 'Company dormitory · JPY 25,000 / month deducted',
            'food' => BenefitProvision::NotProvided,
            'transport' => BenefitProvision::Allowance, 'transport_details' => 'Commuting allowance',
            'medical' => BenefitProvision::Provided, 'medical_details' => 'Japanese social insurance',
            'airfare' => BenefitProvision::Provided, 'airfare_details' => 'Employer pays (arrival)',
            'permit_payer' => CostPayer::Employer,
            'visa_payer' => CostPayer::Employer,
            'deadline' => '2027-01-20',
            'status' => Published::class,
            'published_at' => '2026-10-05 09:00:00',
        ], ['panel-wiring', 'plc-basics', 'maintenance'], [
            [JobCostCategory::SkillsTest, 'Skills test fee (Prometric)', 3000_00, 'BDT', CostPayer::Worker],
        ]);

        $this->jobs['SA-HV-0021'] = $this->job([
            'organization_id' => $this->orgs['northstar']->id,
            'employer_organization_id' => $this->orgs['alnoor']->id,
            'agency_organization_id' => $this->orgs['northstar']->id,
            'job_mandate_id' => $md1121->id,
            'public_code' => 'SA-HV-0021',
            'title' => 'HVAC Technician',
            'occupation' => 'hvac-technician',
            'min_experience_years' => 4,
            'language_requirements' => [['code' => 'en', 'level' => 'basic'], ['code' => 'ar', 'level' => 'basic']],
            'destination_country_code' => 'SA',
            'destination_city' => 'Riyadh',
            'headcount' => 40,
            'salary_amount_minor' => 2500_00,
            'salary_currency' => 'SAR',
            'hours_per_day' => 8,
            'days_per_week' => 6,
            'overtime_terms' => 'Paid at 1.5× basic hourly rate',
            'contract_months' => 24,
            'accommodation' => BenefitProvision::Provided,
            'food' => BenefitProvision::Provided, 'food_details' => '3 meals',
            'transport' => BenefitProvision::Provided,
            'medical' => BenefitProvision::Provided,
            'airfare' => BenefitProvision::Provided, 'airfare_details' => 'Employer pays (both ways)',
            'permit_payer' => CostPayer::Employer,
            'visa_payer' => CostPayer::Employer,
            'deadline' => '2026-12-10',
            // the mandate is still pending, so this job cannot be published (product rule 10)
            'status' => UnderReview::class,
        ], ['split-ac-installation', 'chiller-maintenance', 'refrigerant-handling'], []);

        $this->jobs['CZ-WE-0003'] = $this->job([
            'organization_id' => $this->orgs['moravia']->id,
            'employer_organization_id' => $this->orgs['moravia']->id,
            'public_code' => 'CZ-WE-0003',
            'title' => 'MIG/MAG Welder',
            'occupation' => 'welder',
            'min_experience_years' => 3,
            'language_requirements' => [['code' => 'en', 'level' => 'basic']],
            'destination_country_code' => 'CZ',
            'destination_city' => 'Ostrava',
            'headcount' => 25,
            'salary_amount_minor' => 42000_00,
            'salary_currency' => 'CZK',
            'hours_per_day' => 8,
            'days_per_week' => 5,
            'overtime_terms' => 'Paid per Czech labour code',
            'contract_months' => 12,
            'accommodation' => BenefitProvision::Deducted, 'accommodation_details' => 'Hostel arranged · CZK 5,000 / month deducted',
            'medical' => BenefitProvision::Provided, 'medical_details' => 'Public health insurance',
            'airfare' => BenefitProvision::NotProvided,
            'permit_payer' => CostPayer::Employer,
            'visa_payer' => CostPayer::Worker,
            'deadline' => '2026-12-31',
            'status' => Published::class,
            'published_at' => '2026-10-08 09:00:00',
        ], ['migmag-135136', 'blueprint-reading', 'fillet-welds'], [
            [JobCostCategory::Visa, 'Visa application fee', 2500_00, 'CZK', CostPayer::Worker],
        ]);

        $this->jobs['PL-WH-0014']->verificationRequests()->create(['type' => VerificationType::Job, 'status' => InReview::class, 'assigned_to' => $this->verificationOfficer->id, 'notes' => 'Salary changed after publishing.']);
        $this->jobs['SA-HV-0021']->verificationRequests()->create(['type' => VerificationType::Job, 'status' => OpenRequest::class]);
    }

    private function seedWorkers(): void
    {
        $workers = [
            'rahim' => ['Rahim Uddin', '+8801711000001', 'bn', 'Asia/Dhaka', 'BD', 'electrician', 'industrial-electrician', 'Electrical Trade Professional', 8, Availability::Immediately, PassportStatus::Ready,
                [['BD', 'Industrial electrician', '2016-01-01', '2020-12-31'], ['AE', 'Electrician', '2021-02-01', '2023-12-31']],
                ['industrial-wiring', 'db-installation', 'maintenance'], [['bn', LanguageProficiency::Native], ['hi', LanguageProficiency::Conversational], ['en', LanguageProficiency::Basic]], ['AE', 'PL']],
            'karim' => ['Karim Ahmed', '+8801711000002', 'bn', 'Asia/Dhaka', 'BD', 'electrician', 'industrial-electrician', 'Industrial Electrician', 6, Availability::Within30Days, PassportStatus::Ready,
                [['BD', 'Industrial electrician', '2019-01-01', null]],
                ['industrial-wiring', 'cable-termination'], [['bn', LanguageProficiency::Native], ['en', LanguageProficiency::Basic]], ['AE']],
            'hasan' => ['Hasan Ali', '+923001000003', 'ur', 'Asia/Karachi', 'PK', 'electrician', null, 'Electrical Technician', 10, Availability::Within60Days, PassportStatus::Ready,
                [['PK', 'Electrical technician', '2015-01-01', '2020-12-31'], ['SA', 'Electrical technician', '2021-01-01', '2024-12-31']],
                ['db-installation', 'troubleshooting', 'cable-termination'], [['ur', LanguageProficiency::Native], ['en', LanguageProficiency::Fluent]], ['SA', 'AE']],
            'sunil' => ['Sunil Thapa', '+9779801000004', 'ne', 'Asia/Kathmandu', 'NP', 'electrician', 'building-electrician', 'Electrician', 5, Availability::Immediately, PassportStatus::Applied,
                [['NP', 'Electrician', '2019-06-01', '2022-05-31'], ['QA', 'Electrician', '2022-06-01', '2024-05-31']],
                ['industrial-wiring', 'conduit-bending'], [['ne', LanguageProficiency::Native], ['hi', LanguageProficiency::Conversational]], ['QA', 'AE']],
            'arjun' => ['Arjun Kumar', '+919801000005', 'hi', 'Asia/Kolkata', 'IN', 'electrician', null, 'Electrician', 7, Availability::Within30Days, PassportStatus::Ready,
                [['IN', 'Electrician', '2017-01-01', '2020-12-31'], ['OM', 'Electrician', '2021-01-01', '2023-12-31']],
                ['db-installation', 'maintenance', 'cable-termination'], [['hi', LanguageProficiency::Native], ['ta', LanguageProficiency::Fluent], ['en', LanguageProficiency::Conversational]], ['AE', 'OM']],
            'nimal' => ['Nimal Perera', '+94771000006', 'si', 'Asia/Colombo', 'LK', 'electrician', null, 'Electrician', 4, Availability::Within60Days, PassportStatus::Ready,
                [['LK', 'Electrician', '2021-01-01', null]],
                ['industrial-wiring', 'troubleshooting'], [['si', LanguageProficiency::Native], ['en', LanguageProficiency::Conversational]], ['JP', 'AE']],
            'ravi' => ['Ravi Shankar', '+919801000007', 'hi', 'Asia/Kolkata', 'IN', 'electrician', null, 'Electrician', 6, Availability::Immediately, PassportStatus::Ready,
                [['IN', 'Electrician', '2018-01-01', null]],
                ['industrial-wiring', 'db-installation'], [['hi', LanguageProficiency::Native], ['en', LanguageProficiency::Basic]], ['AE']],
            'saiful' => ['Mohammad Saiful', '+8801711000008', 'bn', 'Asia/Dhaka', 'BD', 'electrician', null, 'Electrician', 9, Availability::NotAvailable, PassportStatus::Ready,
                [['BD', 'Electrician', '2016-01-01', '2026-06-30']],
                ['industrial-wiring', 'maintenance'], [['bn', LanguageProficiency::Native]], ['AE']],
            'tek' => ['Tek Bahadur', '+9779801000009', 'ne', 'Asia/Kathmandu', 'NP', 'hvac-technician', 'split-systems', 'HVAC Technician', 5, Availability::Within30Days, PassportStatus::Ready,
                [['NP', 'HVAC technician', '2020-01-01', null]],
                ['refrigerant-handling', 'split-ac-installation'], [['ne', LanguageProficiency::Native], ['hi', LanguageProficiency::Conversational]], ['SA']],
        ];

        foreach ($workers as $key => [$name, $phone, $locale, $timezone, $country, $occupation, $specialization, $headline, $years, $availability, $passport, $experiences, $skills, $languages, $destinations]) {
            $user = User::query()->create([
                'type' => UserType::Worker,
                'name' => $name,
                'phone' => $phone,
                'locale' => $locale,
                'timezone' => $timezone,
                'data_region' => config('blueskills.default_data_region'),
            ]);
            $user->forceFill(['phone_verified_at' => now()->subMonths(1)])->save();

            $occupationModel = Occupation::query()->where('slug', $occupation)->firstOrFail();

            $profile = WorkerProfile::query()->create([
                'user_id' => $user->id,
                // Rahim keeps the prototype's Passport number; seeding runs without model events.
                'reference' => $key === 'rahim' ? 'BSK-0004182' : WorkerProfile::newReference(),
                'headline' => $headline,
                'occupation_id' => $occupationModel->id,
                'specialization_id' => $specialization ? Specialization::query()->where('occupation_id', $occupationModel->id)->where('slug', $specialization)->value('id') : null,
                'years_experience' => $years,
                'current_country_code' => $country,
                'passport_status' => $passport,
                'availability' => $availability,
                'visibility' => ProfileVisibility::EmployersAndAgencies,
                'profile_completed_at' => now()->subWeeks(3),
            ]);

            foreach ($experiences as [$expCountry, $role, $start, $end]) {
                $profile->experiences()->create(['country_code' => $expCountry, 'role' => $role, 'started_on' => $start, 'ended_on' => $end]);
            }

            foreach ($skills as $slug) {
                $profile->skills()->attach($this->skillId($slug), [
                    'source' => $key === 'rahim' ? SkillSource::Voice : SkillSource::Typed,
                    'confirmed_at' => now()->subWeeks(3),
                ]);
            }

            foreach ($languages as [$code, $proficiency]) {
                $profile->languages()->create(['language_code' => $code, 'proficiency' => $proficiency]);
            }

            foreach ($destinations as $priority => $code) {
                $profile->destinationPreferences()->create(['country_code' => $code, 'priority' => $priority + 1]);
            }

            $profile->consents()->create(['purpose' => ConsentPurpose::Discovery, 'granted_at' => now()->subWeeks(3), 'scope' => ['visibility' => ProfileVisibility::EmployersAndAgencies->value]]);

            $this->workers[$key] = $profile;
        }
    }

    /**
     * Rahim's full Skill Passport: voice answers, SkillShow, documents and evidence.
     */
    private function seedRahimPassport(): void
    {
        $rahim = $this->workers['rahim'];
        $rahim->update(['passport_valid_until_year' => 2030]);
        $confirmedAt = now()->subWeeks(3);

        // Bangla answers from the prototype's Voice-to-CV demo, with the fields the worker confirmed.
        $answers = [
            [VoiceQuestion::Occupation, 'আমি ইলেকট্রিশিয়ান, বেশিরভাগ ইন্ডাস্ট্রিয়াল ওয়্যারিং করি।', ['occupation' => 'electrician', 'specialization' => 'industrial-electrician']],
            [VoiceQuestion::Experience, 'আট বছর।', ['years_experience' => 8]],
            [VoiceQuestion::Countries, 'বাংলাদেশে পাঁচ বছর, দুবাইতে তিন বছর।', ['countries' => [['code' => 'BD', 'years' => 5], ['code' => 'AE', 'years' => 3]]]],
            [VoiceQuestion::Skills, 'ইন্ডাস্ট্রিয়াল ওয়্যারিং, ডিবি ইনস্টলেশন আর মেইনটেন্যান্স।', ['skills' => ['industrial-wiring', 'db-installation', 'maintenance']]],
            [VoiceQuestion::Passport, 'হ্যাঁ, পাসপোর্ট আছে, মেয়াদ ২০৩০ পর্যন্ত।', ['passport_status' => 'ready', 'passport_valid_until' => '2030']],
            [VoiceQuestion::Availability, 'এখনই শুরু করতে পারব।', ['availability' => 'immediately']],
            [VoiceQuestion::Destinations, 'দুবাই, না হলে পোল্যান্ড।', ['destinations' => ['AE', 'PL']]],
        ];

        foreach ($answers as [$question, $transcript, $extracted]) {
            $rahim->voiceAnswers()->create([
                'question_key' => $question,
                'locale' => 'bn',
                'transcript' => $transcript,
                'extracted' => $extracted,
                'status' => VoiceAnswerStatus::Confirmed,
                'confirmed_at' => $confirmedAt,
            ]);
        }

        /** @var SkillShow $skillShow */
        $skillShow = $rahim->skillShows()->create([
            'duration_ms' => 28_000,
            'visibility' => ProfileVisibility::EmployersAndAgencies,
            'moderation_status' => ModerationStatus::Approved,
            'moderated_by' => $this->verificationOfficer->id,
            'moderated_at' => now()->subWeeks(2),
        ]);
        $skillShow->skills()->attach(Skill::query()->whereIn('slug', ['industrial-wiring', 'db-installation'])->pluck('id'));

        $rahim->evidenceItems()->create(['type' => EvidenceType::Identity, 'status' => IssuerVerified::class, 'verified_by' => $this->verificationOfficer->id, 'verified_at' => now()->subWeeks(2)]);

        $documents = [
            [DocumentType::Passport, 'Passport', '2030-06-30', EvidenceType::Passport, UploadedUnreviewed::class],
            [DocumentType::EmployerReference, 'UAE work reference', null, EvidenceType::EmployerReference, VerificationPending::class],
            [DocumentType::Certificate, 'Electrical training certificate', null, EvidenceType::Certificate, IssuerVerified::class],
        ];

        foreach ($documents as [$type, $title, $expires, $evidenceType, $state]) {
            /** @var class-string<EvidenceState> $state */
            $document = Document::query()->create(['owner_user_id' => $rahim->user_id, 'type' => $type, 'title' => $title, 'expires_at' => $expires]);

            if ($type === DocumentType::Passport) {
                // A placeholder file so the Documents page and shared-scan links open something.
                $media = $document->addMediaFromString(self::SAMPLE_PDF)
                    ->usingName('passport-sample.pdf')
                    ->usingFileName('passport.pdf')
                    ->toMediaCollection(Document::FILE_COLLECTION);
                // Seeding runs without model events, so the media library never sets the UUID
                // that private media links use.
                $media->forceFill(['uuid' => (string) str()->uuid()])->save();
            }
            $verified = $state === IssuerVerified::class;

            $evidence = EvidenceItem::query()->create([
                'worker_profile_id' => $rahim->id,
                'type' => $evidenceType,
                'documentable_type' => $document->getMorphClass(),
                'documentable_id' => $document->id,
                'status' => $state,
                'verified_by' => $verified ? $this->verificationOfficer->id : null,
                'verified_at' => $verified ? now()->subWeeks(2) : null,
            ]);

            if ($state === VerificationPending::class) {
                $evidence->verificationRequests()->create(['type' => VerificationType::WorkerEvidence, 'status' => OpenRequest::class]);
            }
        }
    }

    private function seedApplicationsAndCases(): void
    {
        $uae = $this->jobs['UAE-EL-0001'];

        $this->apply('rahim', $uae, Accepted::class, '2026-09-02', ['medical', '2026-10-24']);
        $this->apply('karim', $uae, Accepted::class, '2026-09-03', ['work_permit', '2026-11-02']);
        $this->apply('ravi', $uae, Accepted::class, '2026-09-04', ['visa', '2026-11-09']);
        $this->apply('saiful', $uae, Accepted::class, '2026-07-10', ['joined', null]);
        $this->apply('hasan', $uae, Shortlisted::class, '2026-09-10');
        $this->apply('arjun', $uae, Interview::class, '2026-09-12');
        $this->apply('sunil', $uae, Applied::class, '2026-10-12');
        $this->apply('nimal', $this->jobs['JP-IT-0007'], Applied::class, '2026-10-14');

        $this->seedEmployerPipeline();
        $this->seedConversationsAndAlerts();
    }

    /**
     * Module 5: conversations, in-app notifications, an invitation and a delivery log, so the
     * Messages, inbox and notification pages have something to show (D-073).
     */
    private function seedConversationsAndAlerts(): void
    {
        $imran = $this->orgUsers['imran@northstar.test'];
        $sara = $this->orgUsers['sara@abc-contracting.test'];
        $owner = $this->orgUsers['owner@abc-contracting.test'];
        $uae = $this->jobs['UAE-EL-0001'];
        $abc = $this->jobs['AE-MT-0115'];

        // Prototype "Messages": Rahim and Northstar about the medical test (UAE-EL-0001).
        $this->conversation($this->applicationOf('rahim', $uae), [
            [$imran, 'Your contract is accepted. Next step is the medical test. I have shared the list of approved centres in Dhaka.', '2026-10-14 04:12'],
            [null, 'Thank you. I will book this week.', '2026-10-14 04:40'],
            [$imran, 'Good. Upload the receipt in Documents after the test.', '2026-10-14 04:43'],
        ], workerReadAt: '2026-10-14 05:00', orgReader: $imran);

        // ABC's pipeline: Hasan's offer, Arjun's interview and an unread note to Rahim.
        $this->conversation($this->applicationOf('hasan', $abc), [
            [$sara, 'We have sent you an offer. Please read it and answer by next week.', '2026-10-15 07:00'],
            [null, 'Thank you. Is accommodation shared or single?', '2026-10-16 03:20'],
        ], workerReadAt: '2026-10-15 08:00', orgReader: null);
        $this->conversation($this->applicationOf('arjun', $abc), [
            [$owner, 'Your video interview is booked. The link comes the day before.', '2026-10-18 06:30'],
            [null, 'Thank you, I will be ready.', '2026-10-18 09:10'],
        ], workerReadAt: '2026-10-18 09:10', orgReader: $owner);
        $this->conversation($this->applicationOf('rahim', $abc), [
            [$owner, 'Please bring your training certificate to the interview.', '2026-10-20 05:15'],
        ], workerReadAt: null, orgReader: $owner);

        // Karim is on ABC's shortlist and was invited to apply.
        JobInvitation::query()->create([
            'job_id' => $abc->id,
            'worker_profile_id' => $this->workers['karim']->id,
            'organization_id' => $this->orgs['abc']->id,
            'invited_by' => $owner->id,
            'note' => 'Your cable termination work fits our maintenance team in Dubai.',
            'status' => InvitationStatus::Sent,
        ]);

        $rahim = $this->workers['rahim']->user;
        $this->alert($rahim, NotificationEvent::JobChanged, ['job' => $abc->title, 'org' => 'ABC Contracting'], '/applications/'.$this->applicationOf('rahim', $abc)->id, '2026-10-16 10:05', read: false);
        $this->alert($rahim, NotificationEvent::Shortlisted, ['job' => $abc->title, 'org' => 'ABC Contracting'], '/applications/'.$this->applicationOf('rahim', $abc)->id, '2026-10-14 09:30', read: true);
        $this->alert($rahim, NotificationEvent::WorkerMessage, ['job' => $abc->title, 'org' => 'ABC Contracting', 'preview' => 'Please bring your training certificate to the interview.'], '/messages/'.$this->applicationOf('rahim', $abc)->conversation?->id, '2026-10-20 05:15', read: false);
        $this->alert($this->workers['karim']->user, NotificationEvent::JobInvitation, ['job' => $abc->title, 'org' => 'ABC Contracting'], '/applications', '2026-10-20 08:00', read: false);
        $this->alert($sara, NotificationEvent::OrganizationMessage, ['job' => $abc->title, 'worker' => 'Hasan Ali', 'preview' => 'Thank you. Is accommodation shared or single?'], '/org/messages/'.$this->applicationOf('hasan', $abc)->conversation?->id, '2026-10-16 03:20', read: false);
        $this->alert($owner, NotificationEvent::NewApplicants, ['job' => $abc->title, 'count' => 1], '/org/jobs/AE-MT-0115/applicants?stage=applied', '2026-10-16 06:00', read: false);

        OutboundMessage::query()->create([
            'user_id' => $rahim->id,
            'channel' => DeliveryChannel::WhatsApp,
            'recipient' => (string) $rahim->phone,
            'event' => NotificationEvent::JobChanged,
            'template' => 'blueskills_update',
            'payload' => ['params' => ['job' => $abc->title, 'org' => 'ABC Contracting'], 'url' => '/applications'],
            'status' => DeliveryStatus::Sent,
            'provider_id' => 'sample',
            'attempts' => 1,
            'sent_at' => '2026-10-16 10:05',
        ]);
    }

    private function applicationOf(string $workerKey, JobPosting $job): Application
    {
        return Application::query()->where('job_id', $job->id)->where('worker_profile_id', $this->workers[$workerKey]->id)->firstOrFail();
    }

    /**
     * @param  list<array{0: User|null, 1: string, 2: string}>  $messages  sender (null = the worker), text, UTC time
     */
    private function conversation(Application $application, array $messages, ?string $workerReadAt, ?User $orgReader): void
    {
        $conversation = Conversation::query()->create([
            'application_id' => $application->id,
            'organization_id' => $application->job->organization_id,
            'worker_profile_id' => $application->worker_profile_id,
        ]);
        $workerUserId = $application->workerProfile->user_id;
        $last = null;

        foreach ($messages as [$sender, $body, $at]) {
            $last = Carbon::parse($at, 'UTC');
            $conversation->messages()->forceCreate([
                'sender_user_id' => $sender === null ? $workerUserId : $sender->id,
                'side' => $sender === null ? MessageSide::Worker : MessageSide::Organization,
                'body' => $body,
                'created_at' => $last,
                'updated_at' => $last,
            ]);
        }

        $conversation->update(['last_message_at' => $last]);

        if ($workerReadAt !== null) {
            ConversationRead::query()->create(['conversation_id' => $conversation->id, 'user_id' => $workerUserId, 'last_read_at' => Carbon::parse($workerReadAt, 'UTC')]);
        }

        if ($orgReader !== null) {
            ConversationRead::query()->create(['conversation_id' => $conversation->id, 'user_id' => $orgReader->id, 'last_read_at' => $last]);
        }
    }

    /**
     * @param  array<string, string|int>  $params
     */
    private function alert(User $user, NotificationEvent $event, array $params, string $url, string $at, bool $read): void
    {
        $user->notifications()->create([
            'id' => (string) str()->uuid(),
            'type' => $event->value,
            'data' => ['event' => $event->value, 'params' => $params, 'url' => $url],
            'read_at' => $read ? Carbon::parse($at, 'UTC')->addHour() : null,
            'created_at' => Carbon::parse($at, 'UTC'),
            'updated_at' => Carbon::parse($at, 'UTC'),
        ]);
    }

    /**
     * ABC Contracting's own live job with applicants at every stage (Module 4), so the employer
     * pipeline, interviews, offers and shortlists have something to show.
     */
    private function seedEmployerPipeline(): void
    {
        $job = $this->jobs['AE-MT-0115'] = $this->job([
            'organization_id' => $this->orgs['abc']->id,
            'employer_organization_id' => $this->orgs['abc']->id,
            'public_code' => 'AE-MT-0115',
            'title' => 'Maintenance Electrician',
            'occupation' => 'electrician',
            'min_experience_years' => 4,
            'language_requirements' => [['code' => 'en', 'level' => 'basic']],
            'destination_country_code' => 'AE',
            'destination_city' => 'Dubai',
            'headcount' => 30,
            'salary_amount_minor' => 2100_00,
            'salary_currency' => 'AED',
            'hours_per_day' => 8,
            'days_per_week' => 6,
            'overtime_terms' => 'Paid at 1.25× basic hourly rate',
            'contract_months' => 24,
            'accommodation' => BenefitProvision::Provided,
            'food' => BenefitProvision::Allowance, 'food_details' => 'AED 300 / month',
            'transport' => BenefitProvision::Provided,
            'medical' => BenefitProvision::Provided,
            'airfare' => BenefitProvision::Provided, 'airfare_details' => 'Employer pays (both ways)',
            'permit_payer' => CostPayer::Employer,
            'visa_payer' => CostPayer::Employer,
            'deadline' => '2026-12-15',
            'status' => Published::class,
            'published_at' => '2026-09-28 09:00:00',
            'submitted_at' => '2026-10-15 08:00:00',
            // Overtime terms changed and were approved again after Rahim applied (product rule 5).
            'material_change_at' => '2026-10-16 10:00:00',
        ], ['industrial-wiring', 'maintenance'], []);

        $this->apply('tek', $job, Rejected::class, '2026-10-02', rejection: RejectionReason::RequirementsNotMet);
        $hasan = $this->apply('hasan', $job, OfferStage::class, '2026-09-30');
        $hasan->update(['assigned_user_id' => $this->orgUsers['sara@abc-contracting.test']->id]);
        $this->apply('arjun', $job, Interview::class, '2026-10-05');
        $this->apply('rahim', $job, Shortlisted::class, '2026-10-08', share: [ApplicationConsentItem::PassportScan, ApplicationConsentItem::PhoneNumber]);
        $this->apply('nimal', $job, Reviewed::class, '2026-10-10');
        $this->apply('sunil', $job, Applied::class, '2026-10-15', share: [ApplicationConsentItem::PhoneNumber]);

        $owner = $this->orgUsers['owner@abc-contracting.test'];
        $shortlist = $this->orgs['abc']->shortlists()->create(['name' => 'Dubai towers — electricians', 'job_id' => $job->id, 'created_by' => $owner->id]);
        $shortlist->entries()->create(['worker_profile_id' => $this->workers['karim']->id, 'note' => 'Strong cable termination; ask about availability.', 'added_by' => $owner->id]);
        $shortlist->entries()->create(['worker_profile_id' => $this->workers['ravi']->id, 'note' => null, 'added_by' => $owner->id]);
    }

    /**
     * An application with the real dated snapshot, per-item consent, stage history and, where the
     * stage needs them, interviews and offers (D-062, D-063).
     *
     * @param  class-string<ApplicationState>  $state
     * @param  array{0: string, 1: string|null}|null  $mobility  current stage key and its due date
     * @param  list<ApplicationConsentItem>  $share  Sensitive items shared on top of the defaults.
     */
    private function apply(string $workerKey, JobPosting $job, string $state, string $appliedAt, ?array $mobility = null, array $share = [], ?RejectionReason $rejection = null): Application
    {
        $profile = $this->workers[$workerKey]->fresh() ?? $this->workers[$workerKey];
        $applied = Carbon::parse($appliedAt)->setTime(9, 30);
        $shared = array_values(array_map(
            fn (ShareableItemData $item) => $item->item,
            array_filter(ShareableItems::for($profile), fn (ShareableItemData $item) => $item->available && ($item->sharedByDefault || in_array($item->item, $share, true))),
        ));
        $skillShow = in_array(ApplicationConsentItem::SkillShow, $shared, true) ? ShareableItems::skillShow($profile) : null;
        $scan = in_array(ApplicationConsentItem::PassportScan, $shared, true) ? ShareableItems::passportScan($profile) : null;

        $snapshot = ProfileSnapshotData::capture($profile, $job, $shared);
        $snapshot->takenAt = $applied->toIso8601String();

        $application = Application::query()->create([
            'job_id' => $job->id,
            'worker_profile_id' => $profile->id,
            'status' => $state,
            'applied_at' => $applied,
            'skill_show_id' => $skillShow?->id,
        ]);
        $application->snapshot()->create(['taken_at' => $applied, 'data' => $snapshot->toArray()]);

        foreach (ApplicationConsentItem::cases() as $item) {
            $granted = in_array($item, $shared, true);
            $application->consents()->create([
                'item' => $item,
                'granted' => $granted,
                'granted_at' => $granted ? $applied : null,
                'document_id' => $item === ApplicationConsentItem::PassportScan ? $scan?->id : null,
            ]);
        }

        $profile->consents()->create([
            'purpose' => ConsentPurpose::ApplicationShare,
            'subject_type' => $application->getMorphClass(),
            'subject_id' => $application->id,
            'scope' => ['job' => $job->public_code, 'items' => array_map(fn (ApplicationConsentItem $item) => $item->value, $shared)],
            'granted_at' => $applied,
        ]);

        if ($scan !== null) {
            $profile->consents()->create([
                'purpose' => ConsentPurpose::DocumentShare,
                'subject_type' => $application->getMorphClass(),
                'subject_id' => $application->id,
                'scope' => ['item' => ApplicationConsentItem::PassportScan->value, 'document' => $scan->id],
                'granted_at' => $applied,
            ]);
        }

        $this->seedHistory($application, $job, $state, $applied, $rejection);

        if ($mobility !== null) {
            $this->openMobilityCase($application, $job, ...$mobility);
        }

        return $application;
    }

    /**
     * @param  class-string<ApplicationState>  $state
     */
    private function seedHistory(Application $application, JobPosting $job, string $state, Carbon $applied, ?RejectionReason $rejection): void
    {
        $path = match ($state::getMorphClass()) {
            'reviewed' => ['reviewed'],
            'shortlisted' => ['reviewed', 'shortlisted'],
            'interview' => ['reviewed', 'shortlisted', 'interview'],
            'offer' => ['reviewed', 'shortlisted', 'interview', 'selected', 'offer'],
            'accepted' => ['reviewed', 'shortlisted', 'interview', 'selected', 'offer', 'accepted'],
            'rejected' => ['reviewed', 'rejected'],
            default => [],
        };
        $recruiter = $job->organization->users()->first();
        $from = null;
        $at = $applied;

        foreach (['applied', ...$path] as $index => $to) {
            $at = $index === 0 ? $applied : $applied->copy()->addDays(3 * $index)->min(now()->subHour());
            $application->stageEvents()->forceCreate([
                'from_status' => $from,
                'to_status' => $to,
                'actor_user_id' => $to === 'applied' || $to === 'accepted' ? $application->workerProfile->user_id : $recruiter?->id,
                'reason_code' => $to === 'rejected' ? $rejection?->value : null,
                'created_at' => $at,
                'updated_at' => $at,
            ]);
            $from = $to;
        }

        $application->forceFill(['stage_changed_at' => $at])->save();

        if (in_array('interview', $path, true)) {
            $upcoming = $state === Interview::class;
            $application->interviews()->create([
                'scheduled_at' => $upcoming ? now()->addDays(3)->setTime(6, 0) : $applied->copy()->addDays(9)->setTime(6, 0)->min(now()->subDay()),
                'duration_minutes' => 30,
                'mode' => InterviewMode::Video,
                'location' => 'Video call link is sent the day before',
                'instructions' => 'Keep your Skill Passport and a pen and paper ready.',
                'outcome' => $upcoming ? null : InterviewOutcome::Passed,
                'scheduled_by' => $recruiter?->id,
            ]);
        }

        if (in_array('offer', $path, true)) {
            $accepted = $state === Accepted::class;
            $application->offers()->create([
                'salary_amount_minor' => (int) $job->salary_amount_minor,
                'salary_currency' => (string) $job->salary_currency,
                'salary_period' => $job->salary_period ?? SalaryPeriod::Month,
                'start_on' => $accepted ? null : now()->addMonths(2)->startOfMonth()->toDateString(),
                'respond_by' => $accepted ? null : now()->addWeek()->toDateString(),
                'note' => $accepted ? null : 'Welcome to the maintenance team. Joining in Dubai after your visa is issued.',
                'status' => $accepted ? OfferStatus::Accepted : OfferStatus::Sent,
                'responded_at' => $accepted ? $at : null,
                'sent_by' => $recruiter?->id,
            ]);
        }
    }

    private function openMobilityCase(Application $application, JobPosting $job, string $currentStage, ?string $dueOn): void
    {
        /** @var list<array{key: string, label: string}> $template */
        $template = CountryRule::query()
            ->where('country_code', $job->destination_country_code)
            ->where('key', CountryRuleKey::MobilityStageTemplate)
            ->value('value') ?? [];

        // The AE template ends at arrival & joining; "joined" means every stage is done.
        $keys = array_column($template, 'key');
        $currentIndex = $currentStage === 'joined' ? count($keys) : array_search($currentStage, $keys, true);

        $joined = $currentStage === 'joined';
        $openedAt = Carbon::parse($application->applied_at)->addWeek();
        $case = MobilityCase::query()->create([
            'application_id' => $application->id,
            'template' => $job->destination_country_code,
            // Prototype case MC-UAE-2026-0188, numbered on from there (D-078).
            'reference' => sprintf('MC-%s-2026-%04d', $job->destination_country_code, 188 + MobilityCase::query()->count()),
            'managing_organization_id' => $job->organization_id,
            'employer_organization_id' => $job->employer_organization_id,
            'agency_organization_id' => $job->agency_organization_id,
            'handler_user_id' => $application->assigned_user_id,
            'current_stage' => $joined ? null : $currentStage,
            'status' => $joined ? Completed::class : OpenCase::class,
            'closed_reason' => $joined ? CaseClosedReason::Joined : null,
            'closed_at' => $joined ? $openedAt->copy()->addWeeks(count($keys)) : null,
        ]);
        $case->events()->create(['action' => CaseEventAction::Opened, 'created_at' => $openedAt, 'updated_at' => $openedAt]);

        foreach ($keys as $position => $key) {
            $done = $position < $currentIndex;
            $completedAt = $openedAt->copy()->addWeeks($position);
            $case->stages()->create([
                'key' => $key,
                'position' => $position + 1,
                'status' => $done ? MobilityStageStatus::Done : ($position === $currentIndex ? MobilityStageStatus::InProgress : MobilityStageStatus::Pending),
                'due_on' => $position === $currentIndex ? $dueOn : null,
                'next_action' => $position === $currentIndex && $key === 'medical'
                    ? 'Book a medical test at an approved centre in Dhaka, then upload the receipt.'
                    : null,
                'completed_at' => $done ? $completedAt : null,
            ]);

            if ($done) {
                $case->events()->create(['action' => CaseEventAction::StageCompleted, 'stage_key' => $key, 'created_at' => $completedAt, 'updated_at' => $completedAt]);
            }
        }

        if ($joined) {
            $case->events()->create(['action' => CaseEventAction::Closed, 'created_at' => $case->closed_at, 'updated_at' => $case->closed_at]);
        }

        $this->seedCaseDocuments($case, $keys, (int) $currentIndex, $dueOn, $openedAt);
    }

    /**
     * The destination checklist as far as the case has got (D-079): documents for finished
     * stages are in and accepted, the current stage's document is needed by its due date. The
     * passport copy is the worker's scan on file, kept from the employer (prototype w-case).
     *
     * @param  list<string>  $keys
     */
    private function seedCaseDocuments(MobilityCase $case, array $keys, int $currentIndex, ?string $dueOn, Carbon $openedAt): void
    {
        $case->loadMissing('application.workerProfile', 'managingOrganization.users');
        $workerId = $case->application->workerProfile->user_id;
        $staffId = $case->handler_user_id ?? $case->managingOrganization?->users->first()?->id;
        $stageFor = [DocumentType::Contract->value => ['contract_accepted', 'contract'], DocumentType::Passport->value => ['passport_submitted'],
            DocumentType::Medical->value => ['medical'], DocumentType::PoliceClearance->value => ['police_clearance'], DocumentType::WorkPermit->value => ['work_permit'], DocumentType::Visa->value => ['visa']];

        foreach (CaseChecklist::for($case->template) as $type) {
            $stage = collect($stageFor[$type->value] ?? [])->map(fn (string $key) => array_search($key, $keys, true))->first(fn ($index) => $index !== false);
            // Items with no stage of their own (photo, certificates) are due with the first one.
            $position = is_int($stage) ? $stage : 1;
            $provider = CaseChecklist::provider($type);
            $done = $position < $currentIndex;
            $at = $openedAt->copy()->addWeeks($position);
            $ownerId = $provider === DocumentProvider::Worker ? $workerId : $staffId;

            $document = match (true) {
                ! $done || $ownerId === null => null,
                $type === DocumentType::Passport => Document::query()->where('owner_user_id', $workerId)->where('type', DocumentType::Passport)->first()
                    ?? $this->sampleDocument($workerId, $type, $type->label()),
                default => $this->sampleDocument($ownerId, $type, CaseChecklist::title($type)),
            };

            $item = $case->documents()->create([
                'type' => $type,
                'title' => CaseChecklist::title($type),
                'status' => $document !== null ? CaseDocumentStatus::Accepted : CaseDocumentStatus::Requested,
                'provided_by' => $provider,
                'document_id' => $document?->id,
                'employer_can_view' => CaseChecklist::employerCanViewByDefault($type),
                'shared_at' => $document !== null ? $at : null,
                'decided_at' => $document !== null ? $at : null,
                'decided_by' => $document !== null ? ($type === DocumentType::Contract ? $workerId : $staffId) : null,
                'due_on' => $position === $currentIndex ? $dueOn : null,
            ]);

            if ($document !== null) {
                $case->events()->create([
                    'action' => $type === DocumentType::Contract ? CaseEventAction::ContractConfirmed : CaseEventAction::DocumentAccepted,
                    'case_document_id' => $item->id,
                    'actor_user_id' => $type === DocumentType::Contract ? $workerId : $staffId,
                    'created_at' => $at,
                    'updated_at' => $at,
                ]);
            }
        }
    }

    private function sampleDocument(string $ownerId, DocumentType $type, string $title): Document
    {
        $document = Document::query()->create(['owner_user_id' => $ownerId, 'type' => $type, 'title' => $title]);
        $media = $document->addMediaFromString(self::SAMPLE_PDF)
            ->usingName("{$type->value}-sample.pdf")
            ->usingFileName("{$type->value}.pdf")
            ->toMediaCollection(Document::FILE_COLLECTION);
        $media->forceFill(['uuid' => (string) str()->uuid()])->save();

        return $document;
    }

    private function seedQueues(): void
    {
        // A registration waiting for review (Module 3 organization queue).
        $gulfStar = Organization::query()->create([
            'type' => OrganizationType::Employer,
            'legal_name' => 'Gulf Star Facilities Management LLC',
            'display_name' => 'Gulf Star Facilities',
            'country_code' => 'AE',
            'city' => 'Abu Dhabi',
            'registration_number' => 'ADED-CN-2299871',
            'verification_status' => PendingOrganization::class,
            'data_region' => config('blueskills.default_data_region'),
        ]);
        $gulfStarOwner = $this->user('Mariam Al Suwaidi', 'owner@gulfstar.test', UserType::Org, 'Asia/Dubai');
        $gulfStar->users()->attach($gulfStarOwner->id, ['title' => 'Operations Director', 'joined_at' => now()->subDays(3)]);
        TeamContext::run($gulfStar->id, fn () => $gulfStarOwner->unsetRelation('roles')->assignRole(OrganizationRole::Owner->value));
        $gulfStar->verificationRequests()->create(['type' => VerificationType::Organization, 'status' => OpenRequest::class]);

        // A draft that cannot be submitted yet: workers would pay a recruitment fee (D-053).
        $this->job([
            'organization_id' => $this->orgs['abc']->id,
            'employer_organization_id' => $this->orgs['abc']->id,
            'public_code' => 'AE-ST-0042',
            'title' => 'Steel Fixer',
            'occupation' => 'steel-fixer',
            'destination_country_code' => 'AE',
            'destination_city' => 'Abu Dhabi',
            'headcount' => 60,
            'salary_amount_minor' => 1800_00,
            'salary_currency' => 'AED',
            'hours_per_day' => 8,
            'days_per_week' => 6,
            'contract_months' => 24,
            'accommodation' => BenefitProvision::Provided,
            'status' => Draft::class,
        ], [], [
            [JobCostCategory::RecruitmentFee, 'Recruitment service fee', 4000_00, 'AED', CostPayer::Worker],
        ]);

        Report::query()->create([
            'reporter_user_id' => $this->workers['sunil']->user_id,
            'subject_type' => $this->jobs['PL-WH-0014']->getMorphClass(),
            'subject_id' => $this->jobs['PL-WH-0014']->id,
            'category' => ReportCategory::MisleadingSalary,
            'description' => 'The salary in the listing changed after I applied to a similar job.',
            'status' => OpenReport::class,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $skillSlugs
     * @param  list<array{0: JobCostCategory, 1: string, 2: int, 3: string, 4: CostPayer}>  $costs
     */
    private function job(array $attributes, array $skillSlugs, array $costs): JobPosting
    {
        $occupation = Occupation::query()->where('slug', $attributes['occupation'])->firstOrFail();
        unset($attributes['occupation']);

        $job = JobPosting::query()->create([
            ...$attributes,
            'occupation_id' => $occupation->id,
            'salary_period' => SalaryPeriod::Month,
        ]);

        foreach ($skillSlugs as $slug) {
            $job->skills()->attach($this->skillId($slug), ['id' => (string) str()->ulid(), 'required' => true]);
        }

        foreach ($costs as [$category, $description, $amount, $currency, $payer]) {
            $job->costs()->create(['category' => $category, 'description' => $description, 'amount_minor' => $amount, 'currency' => $currency, 'payer' => $payer]);
        }

        return $job;
    }

    private function skillId(string $slug): string
    {
        return Skill::query()->where('slug', $slug)->firstOrFail()->id;
    }

    private function user(string $name, string $email, UserType $type, string $timezone): User
    {
        $user = User::query()->create([
            'type' => $type,
            'name' => $name,
            'email' => $email,
            'password' => self::PASSWORD,
            'timezone' => $timezone,
            'data_region' => config('blueskills.default_data_region'),
        ]);
        $user->forceFill(['email_verified_at' => now()->subMonths(2)])->save();

        return $user;
    }
}
