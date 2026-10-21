<?php

namespace Database\Seeders;

use App\Domain\Config\Enums\CountryRuleKey;
use App\Domain\Config\Models\CountryRule;
use App\Domain\Jobs\Enums\JobCostCategory;
use Illuminate\Database\Seeder;

/**
 * Destination-country configuration from the prototype's Countries screen. These are
 * placeholders for corridor-specific legal review, not legal advice (spec §10, §16).
 */
class CountryRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            'AE' => [
                CountryRuleKey::Languages->value => ['ar', 'en'],
                CountryRuleKey::AgencyEligibility->value => ['text' => 'Licensed agencies with employer mandate'],
                CountryRuleKey::WorkerCostPolicy->value => ['text' => 'No recruitment fees charged to workers'],
                CountryRuleKey::AllowedWorkerCostCategories->value => [],
                CountryRuleKey::MandatoryDisclosures->value => ['salary', 'accommodation', 'working_hours', 'contract_length'],
                CountryRuleKey::DocumentTypes->value => ['passport', 'photo', 'medical', 'contract'],
                CountryRuleKey::MobilityStageTemplate->value => [
                    ['key' => 'job_selected', 'label' => 'Job selected'],
                    ['key' => 'contract_accepted', 'label' => 'Contract accepted'],
                    // D-074: never "received by agency"; the passport goes back right after stamping.
                    ['key' => 'passport_submitted', 'label' => 'Passport submitted for visa stamping'],
                    ['key' => 'medical', 'label' => 'Medical test'],
                    ['key' => 'police_clearance', 'label' => 'Police clearance'],
                    ['key' => 'work_permit', 'label' => 'Work permit'],
                    ['key' => 'visa', 'label' => 'Visa'],
                    ['key' => 'flight_departure', 'label' => 'Flight & departure'],
                    ['key' => 'arrival_joining', 'label' => 'Arrival & joining'],
                ],
            ],
            'SA' => [
                CountryRuleKey::Languages->value => ['ar', 'en'],
                CountryRuleKey::AgencyEligibility->value => ['text' => 'Licensed agencies with employer mandate'],
                CountryRuleKey::WorkerCostPolicy->value => ['text' => 'No recruitment fees charged to workers'],
                CountryRuleKey::AllowedWorkerCostCategories->value => [],
                CountryRuleKey::MandatoryDisclosures->value => ['salary', 'accommodation', 'food', 'overtime'],
                CountryRuleKey::DocumentTypes->value => ['passport', 'medical', 'police_clearance', 'certificate'],
                CountryRuleKey::MobilityStageTemplate->value => [
                    ['key' => 'contract', 'label' => 'Contract'],
                    ['key' => 'medical', 'label' => 'Medical'],
                    ['key' => 'visa', 'label' => 'Visa'],
                    ['key' => 'flight', 'label' => 'Flight'],
                    ['key' => 'iqama', 'label' => 'Iqama'],
                    ['key' => 'joined', 'label' => 'Joined'],
                ],
            ],
            'PL' => [
                CountryRuleKey::Languages->value => ['pl', 'en'],
                CountryRuleKey::AgencyEligibility->value => ['text' => 'Registered employment agencies (KRAZ)'],
                CountryRuleKey::WorkerCostPolicy->value => ['text' => 'Lawful visa and consular fees may be worker-paid if disclosed'],
                CountryRuleKey::AllowedWorkerCostCategories->value => [JobCostCategory::Visa->value, JobCostCategory::Medical->value],
                CountryRuleKey::MandatoryDisclosures->value => ['gross_pay', 'working_hours', 'accommodation_cost', 'contract_type'],
                CountryRuleKey::DocumentTypes->value => ['passport', 'work_permit', 'visa'],
                CountryRuleKey::MobilityStageTemplate->value => [
                    ['key' => 'work_permit', 'label' => 'Work permit'],
                    ['key' => 'visa_appointment', 'label' => 'Visa appointment'],
                    ['key' => 'national_visa', 'label' => 'National visa'],
                    ['key' => 'travel', 'label' => 'Travel'],
                    ['key' => 'joined', 'label' => 'Joined'],
                ],
            ],
            'JP' => [
                CountryRuleKey::Languages->value => ['ja', 'en'],
                CountryRuleKey::AgencyEligibility->value => ['text' => 'Registered support organisations'],
                CountryRuleKey::WorkerCostPolicy->value => ['text' => 'Skills test fees may be worker-paid; brokerage fees not allowed'],
                CountryRuleKey::AllowedWorkerCostCategories->value => [JobCostCategory::SkillsTest->value, JobCostCategory::LanguageTest->value],
                CountryRuleKey::MandatoryDisclosures->value => ['monthly_pay', 'deductions', 'dormitory_cost'],
                CountryRuleKey::DocumentTypes->value => ['passport', 'certificate', 'visa'],
                CountryRuleKey::MobilityStageTemplate->value => [
                    ['key' => 'skills_test', 'label' => 'Skills test'],
                    ['key' => 'language_test', 'label' => 'JLPT'],
                    ['key' => 'coe', 'label' => 'Certificate of Eligibility'],
                    ['key' => 'visa', 'label' => 'Visa'],
                    ['key' => 'travel', 'label' => 'Travel'],
                    ['key' => 'joined', 'label' => 'Joined'],
                ],
            ],
            'CZ' => [
                CountryRuleKey::Languages->value => ['cs', 'en'],
                CountryRuleKey::AgencyEligibility->value => ['text' => 'Licensed employment agencies'],
                CountryRuleKey::WorkerCostPolicy->value => ['text' => 'Visa fees may be worker-paid if disclosed'],
                CountryRuleKey::AllowedWorkerCostCategories->value => [JobCostCategory::Visa->value],
                CountryRuleKey::MandatoryDisclosures->value => ['gross_pay', 'hostel_deduction', 'shift_pattern'],
                CountryRuleKey::DocumentTypes->value => ['passport', 'work_permit'],
                CountryRuleKey::MobilityStageTemplate->value => [
                    ['key' => 'job_registration', 'label' => 'Job registration'],
                    ['key' => 'employee_card', 'label' => 'Employee card'],
                    ['key' => 'visa', 'label' => 'Visa'],
                    ['key' => 'travel', 'label' => 'Travel'],
                    ['key' => 'joined', 'label' => 'Joined'],
                ],
            ],
        ];

        foreach ($rules as $countryCode => $values) {
            foreach ($values as $key => $value) {
                CountryRule::query()->updateOrCreate(
                    ['country_code' => $countryCode, 'key' => $key],
                    ['value' => $value],
                );
            }
        }
    }
}
