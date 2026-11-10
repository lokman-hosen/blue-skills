<?php

namespace Database\Seeders;

use App\Domain\Identity\Models\User;
use App\Domain\Jobs\Enums\AgeRange;
use App\Domain\Jobs\Enums\BenefitProvision;
use App\Domain\Jobs\Enums\ContractDuration;
use App\Domain\Jobs\Enums\CostPayer;
use App\Domain\Jobs\Enums\EmploymentType;
use App\Domain\Jobs\Enums\ExperienceLevel;
use App\Domain\Jobs\Enums\JobCostCategory;
use App\Domain\Jobs\Enums\SalaryBasis;
use App\Domain\Jobs\Enums\SalaryPeriod;
use App\Domain\Jobs\Enums\ShiftPattern;
use App\Domain\Jobs\Enums\WorkerFees;
use App\Domain\Jobs\Enums\WorkingDays;
use App\Domain\Jobs\Enums\WorkingHours;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Published;
use App\Domain\Organization\Models\Organization;
use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Skill;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The twelve jobs of "EU Work Permit Program — Job Options List" (updated Oct 2026), posted by the
 * sample employer of owner@abc-contracting.test. Runs after SampleWorldSeeder and is safe to run
 * again: a job whose code already exists is left alone.
 *
 * Only what the list states is stored (product rule 4). The list gives no vacancy count, deadline,
 * or who pays for the visa and permit, so the deadline and payers stay "Not disclosed" and
 * HEADCOUNT is a placeholder. Worker fees are "apply" only where the list names a cost the worker
 * pays; otherwise they stay unknown.
 */
class EuWorkPermitJobsSeeder extends Seeder
{
    public const OWNER_EMAIL = 'owner@abc-contracting.test';

    /** The list does not say how many workers each job needs; the column is required. */
    private const HEADCOUNT = 10;

    private const PUBLISHED_AT = '2026-10-15 09:00:00';

    private const POLAND_MOBILITY = <<<'TEXT'
        Work permit: valid for 12–36 months. Once it is issued, you must register for a visa appointment within 3 months; if not, the documents are cancelled without the right to reprocess.
        Temporary residence (TR): after 2–3 months of work in Poland you can apply for a TR permit yourself; in most cases the employer supports the process. Processing takes up to 6 months. The first TR is issued for 1 year and can be extended.
        TEXT;

    private const CZECHIA_MOBILITY = <<<'TEXT'
        Work permit: valid for 9 months. The employer handles any extension after you arrive in the Czech Republic.
        Work visa: you must register for a visa appointment within 3 months of the work permit being issued; if not, the documents are cancelled.
        Residence: you can extend your stay without returning home by applying for a residence permit. This is decided by the employer alone, based on your work.
        TEXT;

    private const ALBANIA_MOBILITY = <<<'TEXT'
        Work visa: issued for 1 year and extended by the employer if you perform well. You apply online; once approved, the visa sticker is sent to the employer and then to you to place in your passport.
        Residence: after entering Albania you apply for a residence permit, issued by the Albanian immigration authorities.
        TEXT;

    public function run(): void
    {
        $owner = User::query()->where('email', self::OWNER_EMAIL)->first();
        $organization = $owner?->organizations()->first();

        if (! $organization instanceof Organization) {
            $this->command?->warn('EuWorkPermitJobsSeeder: '.self::OWNER_EMAIL.' has no organization yet; run SampleWorldSeeder first.');

            return;
        }

        DB::transaction(function () use ($organization) {
            foreach ($this->jobs() as $job) {
                $this->post($organization, $job);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $job
     */
    private function post(Organization $organization, array $job): void
    {
        if (JobPosting::withTrashed()->where('public_code', $job['public_code'])->exists()) {
            return;
        }

        $skills = $job['skills'];
        $costs = $job['costs'] ?? [];
        $occupation = Occupation::query()->where('slug', $job['occupation'])->firstOrFail();
        unset($job['skills'], $job['costs'], $job['occupation']);

        $level = $job['experience_level'] ?? null;

        $posting = JobPosting::query()->create([
            'organization_id' => $organization->id,
            'employer_organization_id' => $organization->id,
            'occupation_id' => $occupation->id,
            'headcount' => self::HEADCOUNT,
            'min_experience_years' => $level instanceof ExperienceLevel ? $level->minYears() : null,
            'language_requirements' => [],
            'status' => Published::class,
            'published_at' => self::PUBLISHED_AT,
            'submitted_at' => self::PUBLISHED_AT,
            ...$job,
        ]);

        foreach ($skills as $slug) {
            $posting->skills()->attach(
                Skill::query()->where('slug', $slug)->firstOrFail()->id,
                ['id' => (string) str()->ulid(), 'required' => true],
            );
        }

        foreach ($costs as [$category, $description, $amount, $currency]) {
            $posting->costs()->create([
                'category' => $category, 'description' => $description, 'amount_minor' => $amount,
                'currency' => $currency, 'payer' => CostPayer::Worker,
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function jobs(): array
    {
        return [
            [
                'public_code' => 'PL-WE-2601',
                'title' => 'MIG/MAG Welder',
                'occupation' => 'welder',
                'employment_type' => EmploymentType::FullTime,
                'destination_country_code' => 'PL',
                'destination_city' => 'Radomsko',
                'salary_amount_minor' => 40_00,
                'salary_max_amount_minor' => 45_00,
                'salary_currency' => 'PLN',
                'salary_period' => SalaryPeriod::Hour,
                'salary_basis' => SalaryBasis::Net,
                'working_hours' => WorkingHours::Day8To10,
                'shift_pattern' => ShiftPattern::Rotating,
                'shift_pattern_other' => null,
                'overtime_terms' => 'Optional work on Saturdays and Sundays. The schedule is agreed with the manager.',
                'contract_duration' => ContractDuration::Months12To36,
                'accommodation' => BenefitProvision::Deducted,
                'accommodation_cost_amount_minor' => 400_00,
                'accommodation_details' => 'Company accommodation · PLN 400 / month deducted for utilities only',
                'medical' => BenefitProvision::Provided,
                'medical_details' => 'Medical examination paid by the employer',
                'other_benefits' => "Free work clothes and safety shoes.\nHealth and safety (BHP) training free of charge.\nPLN 2.50 / hour extra if you have your own accommodation.",
                'experience_level' => ExperienceLevel::Other,
                'experience_other' => 'Experience in MIG/MAG welding',
                'language_requirements' => [['code' => 'en', 'level' => 'communicative']],
                'other_skills' => 'Welding certificate (required; help to obtain it may be available). Ability to read technical drawings.',
                'duties' => "Multi-pass and single-pass fillet welds.\nButt welds on steel sheets from 3 mm to 120 mm thick.\nWork in various welding positions (PA, PB, PF, EC).\nSteel grades: S235, S355, S420, S460.\nAll welds are subject to UT, RT, MT and PT inspection.",
                'additional_details' => "Pay depends on experience, certificates and additional skills.\nAn initial interview and the factory welding tests are mandatory.\n".self::POLAND_MOBILITY,
                'skills' => ['migmag-135136', 'fillet-welds', 'blueprint-reading'],
            ],
            [
                'public_code' => 'PL-PR-2602',
                'title' => 'Automotive Factory Worker – Sound Insulation Production',
                'occupation' => 'production-worker',
                'employment_type' => EmploymentType::FullTime,
                'destination_country_code' => 'PL',
                'destination_city' => 'Gliwice',
                'salary_amount_minor' => 22_50,
                'salary_currency' => 'PLN',
                'salary_period' => SalaryPeriod::Hour,
                'salary_basis' => SalaryBasis::Net,
                'working_hours' => WorkingHours::Other,
                'working_hours_other' => '12-hour shifts (2-shift system) or 8-hour shifts (3-shift system)',
                'shift_pattern' => ShiftPattern::Rotating,
                'overtime_terms' => 'Optional work on Saturdays and Sundays.',
                'contract_duration' => ContractDuration::Months12To36,
                'accommodation' => BenefitProvision::Deducted,
                'accommodation_cost_amount_minor' => 400_00,
                'accommodation_details' => 'Company accommodation, 2–4 persons per room · PLN 400 / month for utilities',
                'medical' => BenefitProvision::Provided,
                'medical_details' => 'Medical examination paid by the employer',
                'other_benefits' => "Free workwear and safety shoes.\nHealth and safety (BHP) training paid by the employer.\nPLN 2 / hour extra for workers under 26.\nPLN 2.50 / hour extra if you have your own accommodation.",
                'experience_level' => ExperienceLevel::None,
                'age_range' => AgeRange::From18To50,
                'duties' => "Loading raw materials into the machine.\nAdjusting and positioning the material.\nOperating the press.\nRemoving finished parts and placing them on pallets.",
                'additional_details' => "Day and night shifts are available.\nTrainers are present on every shift and teach the complete production process.\nBefore starting, candidates have a preliminary tour of the factory.\n".self::POLAND_MOBILITY,
                'skills' => ['machine-operation'],
            ],
            [
                'public_code' => 'PL-WA-2603',
                'title' => 'Warehouse Worker (Branded Clothing)',
                'occupation' => 'warehouse-operative',
                'employment_type' => EmploymentType::FullTime,
                'destination_country_code' => 'PL',
                'destination_city' => 'Goleniów',
                'salary_amount_minor' => 25_36,
                'salary_currency' => 'PLN',
                'salary_period' => SalaryPeriod::Hour,
                'salary_basis' => SalaryBasis::Net,
                'working_hours' => WorkingHours::Other,
                'working_hours_other' => '168–300 hours / month; more hours are usual in autumn',
                'working_days' => WorkingDays::Rotating,
                'working_days_other' => null,
                'shift_pattern' => ShiftPattern::Rotating,
                'overtime_terms' => 'Saturdays and Sundays may be working days; the schedule may include 5, 6 or 7 working days per week.',
                'contract_duration' => ContractDuration::Months12To36,
                'accommodation' => BenefitProvision::Deducted,
                'accommodation_cost_amount_minor' => 750_00,
                'accommodation_details' => 'PLN 750 / month deducted · rooms for couples available · bring your own bedding',
                'food' => BenefitProvision::Allowance,
                'food_details' => 'Lunch subsidy PLN 9 / day · free fruit snacks 3 times a week',
                'transport' => BenefitProvision::Deducted,
                'transport_details' => 'PLN 20 / month deducted · free city transport Mon–Fri · employer transport for weekend, late and night shifts',
                'other_benefits' => "Bonus of up to 15% for meeting targets and good attendance.\nWork clothes (black T-shirt and trousers) and work shoes provided.",
                'age_range' => AgeRange::Other,
                'age_other' => 'Up to 50 years old',
                'language_requirements' => [['code' => 'en', 'level' => 'A2 or higher']],
                'other_skills' => 'Good physical condition. Ready for shift work.',
                'duties' => "Working with a scanner.\nPicking orders.\nPacking orders according to requirements and applying labels.\nPreparing goods for shipment; placing orders into large boxes and on pallets.",
                'additional_details' => "Two shifts: 06:00–18:00 and 18:00–04:00 / 18:00–06:00. Shifts change every 5 days, followed by 2 days off.\nA 12-hour shift has a 40-minute break, plus 10 minutes in hot weather.\nMinimum employment period: 4 months. An initial interview is required.\n".self::POLAND_MOBILITY,
                'worker_fees' => WorkerFees::Apply,
                'skills' => ['picking-packing', 'scanner-use'],
                'costs' => [
                    [JobCostCategory::Accommodation, 'Accommodation deposit on moving in (refunded after 30 days of work)', 200_00, 'PLN'],
                ],
            ],
            [
                'public_code' => 'PL-FO-2604',
                'title' => 'Poultry Processing Plant Worker',
                'occupation' => 'food-processing-worker',
                'employment_type' => EmploymentType::FullTime,
                'destination_country_code' => 'PL',
                'destination_city' => 'Wilczeniec Bogucki',
                'salary_amount_minor' => 25_36,
                'salary_currency' => 'PLN',
                'salary_period' => SalaryPeriod::Hour,
                'salary_basis' => SalaryBasis::Net,
                'working_hours' => WorkingHours::Month200To270,
                'shift_pattern' => ShiftPattern::Night,
                'overtime_terms' => '220 working hours per month are guaranteed, with the opportunity to work additional hours.',
                'contract_duration' => ContractDuration::Months12To36,
                'accommodation' => BenefitProvision::Deducted,
                'accommodation_cost_amount_minor' => 750_00,
                'accommodation_details' => 'PLN 750 / month deducted from salary · available for couples',
                'duties' => "Removing membranes from chicken fillets on the production line.\nCutting chicken breast fillets with a knife as they move along the line.\nInspecting fillets before packaging.\nPreparing and hanging chicken carcasses before filleting and after scalding.\nSorting poultry offal.\nHanging live chickens by the legs.",
                'additional_details' => "Night shifts only, starting at 23:00 or 00:00.\nFrom approximately PLN 5,580 net per month.\nRitual slaughter is carried out at the plant; products are supplied to companies in France.\n".self::POLAND_MOBILITY,
                'worker_fees' => WorkerFees::Apply,
                'skills' => ['knife-work', 'production-line-work'],
                'costs' => [
                    [JobCostCategory::Accommodation, 'Security deposit before starting work (refunded after 3 months of employment)', 400_00, 'PLN'],
                ],
            ],
            [
                'public_code' => 'PL-PR-2605',
                'title' => 'Mattress Production Worker',
                'occupation' => 'production-worker',
                'employment_type' => EmploymentType::FullTime,
                'destination_country_code' => 'PL',
                'destination_city' => null,
                'salary_amount_minor' => 3400_00,
                'salary_max_amount_minor' => 5300_00,
                'salary_currency' => 'PLN',
                'salary_period' => SalaryPeriod::Month,
                'salary_basis' => SalaryBasis::Net,
                'working_hours' => WorkingHours::Month160To200,
                'working_days' => WorkingDays::Five,
                'shift_pattern' => ShiftPattern::Rotating,
                'overtime_terms' => '+50% for work beyond 8 hours a day, +20% for night shifts, +100% for weekends or public holidays without an additional day off.',
                'contract_duration' => ContractDuration::Months12To36,
                'accommodation' => BenefitProvision::Deducted,
                'accommodation_cost_amount_minor' => 750_00,
                'accommodation_details' => 'Rooms for 2–4 people · PLN 750 / month deducted · 10–15 minutes on foot or a short bus ride',
                'medical' => BenefitProvision::Provided,
                'medical_details' => 'Employer arranges and pays for the required medical certificate',
                'other_benefits' => "Performance bonus of about PLN 300 / month.\n+12% for employees under 26.\nPLN 1 / hour extra if you rent your own accommodation.\nPaid annual leave: 20 or 26 days a year. Paid sick leave: 80% of salary.\nFree work clothing. Canteen and vending machines on site.",
                'age_range' => AgeRange::Other,
                'age_other' => 'Up to 58 years old',
                'other_skills' => 'Ability to work in a team. Responsibility and motivation.',
                'duties' => "Filling and packing pillows and mattresses.\nWorking with plastic film and cardboard boxes.\nManufacturing headboards and garden furniture.\nGluing, upholstering and packing products.\nOperating spring-manufacturing equipment.\nPlacing rolls of batting material.",
                'additional_details' => "Location: regions of Poland.\nThe monthly salary includes overtime and bonuses.\nThree 8-hour shifts (06:00–14:00, 14:00–22:00, 22:00–06:00), Monday to Friday with occasional Saturdays.\n".self::POLAND_MOBILITY,
                'skills' => ['machine-operation', 'product-packing'],
            ],
            [
                'public_code' => 'PL-PR-2606',
                'title' => 'Household Chemicals Production Worker',
                'occupation' => 'production-worker',
                'employment_type' => EmploymentType::FullTime,
                'destination_country_code' => 'PL',
                'destination_city' => null,
                'salary_amount_minor' => 24_00,
                'salary_max_amount_minor' => 24_80,
                'salary_currency' => 'PLN',
                'salary_period' => SalaryPeriod::Hour,
                'salary_basis' => SalaryBasis::Net,
                'working_hours' => WorkingHours::Day6To8,
                'working_days' => WorkingDays::Five,
                'shift_pattern' => ShiftPattern::Rotating,
                'overtime_terms' => 'When the workload increases, additional shifts on Saturdays and Sundays may be offered.',
                'contract_duration' => ContractDuration::Months12To36,
                'accommodation' => BenefitProvision::Deducted,
                'accommodation_cost_amount_minor' => 800_00,
                'accommodation_details' => 'PLN 800–850 / month',
                'medical' => BenefitProvision::Deducted,
                'medical_details' => 'Medical examination PLN 150, deducted from the first salary',
                'age_range' => AgeRange::Other,
                'age_other' => 'Up to 60 years old',
                'language_requirements' => [['code' => 'en', 'level' => 'communicative']],
                'other_skills' => 'No allergy or increased sensitivity to household chemicals. Willing to work in shifts.',
                'duties' => "Sorting products.\nPacking finished products.\nChecking the quality of packaging.\nKeeping the workplace clean and organized.\nSome heavier physical work, such as loading 15–20 kg bags into the mixing machine.",
                'additional_details' => "Location: regions of Poland.\nPLN 24 net / hour is the standard rate; heavier physical work may be paid PLN 24.80 net / hour.\nThe factory makes laundry detergents, dishwasher tablets, air fresheners and bath bombs, so the workplace has a characteristic smell.\nThe T-shirts, shoes and medical examination are not paid in advance; they are deducted from the first salary. You may use your own closed work shoes if they meet safety requirements.\n".self::POLAND_MOBILITY,
                'worker_fees' => WorkerFees::Apply,
                'skills' => ['product-packing', 'quality-checking'],
                'costs' => [
                    [JobCostCategory::Medical, 'Medical examination (deducted from the first salary)', 150_00, 'PLN'],
                    [JobCostCategory::Other, 'Two work T-shirts, PLN 45 each (deducted from the first salary)', 90_00, 'PLN'],
                    [JobCostCategory::Other, 'Closed work shoes, only if bought through the company', 85_00, 'PLN'],
                ],
            ],
            [
                'public_code' => 'CZ-FO-2607',
                'title' => 'Forestry Worker',
                'occupation' => 'forestry-worker',
                'employment_type' => EmploymentType::Seasonal,
                'destination_country_code' => 'CZ',
                'destination_city' => null,
                'salary_amount_minor' => 120_00,
                'salary_max_amount_minor' => 140_00,
                'salary_currency' => 'CZK',
                'salary_period' => SalaryPeriod::Hour,
                'working_hours' => WorkingHours::Other,
                'working_hours_other' => '160–270 hours / month (minimum 160; more on your request)',
                'working_days' => WorkingDays::Six,
                'contract_duration' => ContractDuration::Months6,
                'accommodation' => BenefitProvision::Deducted,
                'accommodation_cost_amount_minor' => 3600_00,
                'accommodation_details' => 'Double rooms · CZK 120 / day (about CZK 3,600 for a 30-day month)',
                'age_range' => AgeRange::From18To60,
                'duties' => "Planting trees, shrubs and forest vegetation.\nTree marking.\nClearing forest areas (weeds, deadwood, litter).\nCaring for young plantations.\nFire prevention and forest protection.",
                'additional_details' => "Location: regions of the Czech Republic. Seasonal work, 6 months. Sunday is a day off.\n".self::CZECHIA_MOBILITY,
                'skills' => ['tree-planting', 'forest-clearing'],
            ],
            [
                'public_code' => 'CZ-CO-2608',
                'title' => 'Construction Workers',
                'occupation' => 'construction-worker',
                'employment_type' => EmploymentType::Seasonal,
                'destination_country_code' => 'CZ',
                'destination_city' => 'Chomutov',
                'salary_amount_minor' => 160_00,
                'salary_max_amount_minor' => 200_00,
                'salary_currency' => 'CZK',
                'salary_period' => SalaryPeriod::Hour,
                'working_hours' => WorkingHours::Day10To12,
                'working_days' => WorkingDays::Six,
                'contract_duration' => ContractDuration::Months6,
                'accommodation' => BenefitProvision::Deducted,
                'accommodation_cost_amount_minor' => 4000_00,
                'accommodation_details' => 'Accommodation provided · CZK 4,000 / month',
                'transport' => BenefitProvision::Provided,
                'transport_details' => 'Free transportation to the workplace',
                'insurance' => BenefitProvision::Provided,
                'insurance_details' => 'Social and health insurance provided',
                'experience_level' => ExperienceLevel::Other,
                'experience_other' => 'Experience in construction',
                'other_skills' => 'Physical endurance and reliability. Willing to work long hours.',
                'duties' => "Work on construction sites in Chomutov:\nPaving stone installation.\nPlastering works.\nInterior finishing works.\nFacade works.",
                'additional_details' => "Skilled workers: CZK 190–200 / hour. Helpers and general labourers: CZK 160–170 / hour.\nSeasonal work, 6 months; the work permit can be extended up to 2 years.\n".self::CZECHIA_MOBILITY,
                'skills' => ['plastering', 'paving', 'facade-works', 'interior-finishing'],
            ],
            [
                'public_code' => 'RS-FA-2609',
                'title' => 'Agriculture Work',
                'occupation' => 'farm-worker',
                'employment_type' => EmploymentType::Seasonal,
                'destination_country_code' => 'RS',
                'destination_city' => 'Novi Sad',
                'salary_amount_minor' => 80000_00,
                'salary_max_amount_minor' => 90000_00,
                'salary_currency' => 'RSD',
                'salary_period' => SalaryPeriod::Month,
                'contract_duration' => ContractDuration::Months6,
                'accommodation' => BenefitProvision::Provided,
                'accommodation_details' => 'Free · 2–4 people per room, no bunk beds · separate room for married couples · shared kitchen, Wi-Fi, hot water',
                'food' => BenefitProvision::Provided,
                'food_details' => 'Meals provided',
                'visa_payer' => CostPayer::Worker,
                'duties' => "Fruit tree care.\nPruning and tying branches.\nOrchard maintenance.\nFarm preparation for the season.\nLight agricultural work (no heavy lifting).",
                'additional_details' => "Seasonal work for 6 months, June to November, in the Novi Sad area.\nWork permit: you receive a package of electronic documents.\nWork visa: check with the consulate which documents it needs and start preparing them as soon as you apply. The D-type visa is issued for 1 year and can be extended for another year.\nTemporary residence: yes.",
                'worker_fees' => WorkerFees::Apply,
                'skills' => ['orchard-care'],
                'costs' => [
                    [JobCostCategory::Visa, 'Visa service fees (up to EUR 260)', 260_00, 'EUR'],
                ],
            ],
            [
                'public_code' => 'LT-CO-2610',
                'title' => 'Construction Helpers',
                'occupation' => 'construction-worker',
                'employment_type' => EmploymentType::FullTime,
                'destination_country_code' => 'LT',
                'destination_city' => null,
                'salary_amount_minor' => 7_50,
                'salary_max_amount_minor' => 8_00,
                'salary_currency' => 'EUR',
                'salary_period' => SalaryPeriod::Hour,
                'working_hours' => WorkingHours::Other,
                'working_hours_other' => '8–12 hours / day',
                'working_days' => WorkingDays::Six,
                'contract_duration' => ContractDuration::Months12To24,
                'accommodation' => BenefitProvision::Deducted,
                'accommodation_cost_amount_minor' => 120_00,
                'accommodation_details' => 'EUR 120–200 / month',
                'experience_level' => ExperienceLevel::Other,
                'experience_other' => 'Previous experience in construction is an advantage',
                'other_skills' => 'Physical fitness and readiness for manual work. Reliable, disciplined and able to work in a team.',
                'duties' => "Assisting skilled construction workers.\nCarrying and preparing materials.\nHelping with tools and equipment.\nCleaning the work area.\nSupporting concrete, installation, finishing or other construction tasks.\nFollowing the supervisor's instructions.",
                'additional_details' => "Location: regions of Lithuania. Employment contract of 1–2 years. Sunday is a day off.\nWork permit: you receive a package of documents from the employer.\nResidence permit: the employer helps you apply through the Lithuanian Migration Information System (MIGRIS). You fill in the application online, then visit a Migration Department branch in Lithuania or a VFS Global centre abroad within 4 months to give biometric data and original documents. The state levy is EUR 160 for standard processing. Once approved, you collect the card at the Migration Department branch you chose.",
                'skills' => [],
            ],
            [
                'public_code' => 'AL-ST-2611',
                'title' => 'Assembly Worker (Steel Structures)',
                'occupation' => 'steel-structure-assembler',
                'employment_type' => EmploymentType::FullTime,
                'destination_country_code' => 'AL',
                'destination_city' => null,
                'salary_amount_minor' => 550_00,
                'salary_currency' => 'EUR',
                'salary_period' => SalaryPeriod::Month,
                'working_hours' => WorkingHours::Day6To8,
                'working_days' => WorkingDays::FiveToSix,
                'contract_duration' => ContractDuration::Years2,
                'accommodation' => BenefitProvision::Provided,
                'accommodation_details' => 'Provided by the employer',
                'food' => BenefitProvision::Provided,
                'food_details' => 'Meals included',
                'other_benefits' => 'Performance bonus: EUR 100–400.',
                'experience_level' => ExperienceLevel::OneToThree,
                'min_experience_years' => 2,
                'age_range' => AgeRange::Other,
                'age_other' => '20–45 years old',
                'other_skills' => '2 years of experience in steel fabrication or industrial assembly. Reads assembly drawings. MIG/MAG welding experience is an advantage. Good physical condition.',
                'duties' => "Assembling steel components and parts according to technical drawings and work instructions.\nPositioning and fixing parts accurately on the assembly table before further processing or welding.\nUsing hand tools and measuring instruments to ensure correct dimensions and alignment.\nChecking assembled components to meet quality standards.\nMaintaining tools and equipment in good working condition.\nSupporting other production processes when required.",
                'additional_details' => "Location: regions of Albania. 8 working hours a day, 5–6 days a week; Sunday is a day off.\nEmployment contract: 2 years.\n".self::ALBANIA_MOBILITY,
                'skills' => ['steel-assembly', 'measuring-instruments'],
            ],
            [
                'public_code' => 'AL-HO-2612',
                'title' => 'Housekeeping',
                'occupation' => 'housekeeping-attendant',
                'employment_type' => EmploymentType::FullTime,
                'destination_country_code' => 'AL',
                'destination_city' => null,
                'salary_amount_minor' => 550_00,
                'salary_currency' => 'EUR',
                'salary_period' => SalaryPeriod::Month,
                'working_hours' => WorkingHours::Day6To8,
                'working_days' => WorkingDays::Six,
                'contract_duration' => ContractDuration::Years2,
                'accommodation' => BenefitProvision::Provided,
                'accommodation_details' => 'Provided by the employer',
                'food' => BenefitProvision::Provided,
                'food_details' => 'Meals included',
                'other_benefits' => 'Stable working environment with a supportive team.',
                'age_range' => AgeRange::Other,
                'age_other' => '25–35 years old',
                'duties' => "Cleaning guest rooms and common areas.\nChanging bed linens and towels.\nMaintaining cleanliness of bathrooms and living areas.\nPreparing rooms according to hospitality standards.\nReporting maintenance issues when necessary.\nAssisting with other housekeeping duties assigned by the supervisor.",
                'additional_details' => "Location: regions of Albania. 8 working hours a day, 6 days a week; Sunday is a day off.\nEmployment contract: 2 years.\n".self::ALBANIA_MOBILITY,
                'skills' => ['room-cleaning', 'laundry'],
            ],
        ];
    }
}
