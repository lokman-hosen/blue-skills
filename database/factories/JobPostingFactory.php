<?php

namespace Database\Factories;

use App\Domain\Jobs\Enums\BenefitProvision;
use App\Domain\Jobs\Enums\ContractDuration;
use App\Domain\Jobs\Enums\CostPayer;
use App\Domain\Jobs\Enums\EmploymentType;
use App\Domain\Jobs\Enums\SalaryBasis;
use App\Domain\Jobs\Enums\SalaryPeriod;
use App\Domain\Jobs\Enums\WorkingDays;
use App\Domain\Jobs\Enums\WorkingHours;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Jobs\States\Job\Draft;
use App\Domain\Jobs\States\Job\Published;
use App\Domain\Organization\Models\Organization;
use App\Domain\Taxonomy\Models\Occupation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<JobPosting>
 */
class JobPostingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => fn (array $attributes) => $attributes['employer_organization_id'],
            'employer_organization_id' => Organization::factory()->employer(),
            'public_code' => fn () => Str::upper(fake()->unique()->bothify('??-??-####')),
            'title' => fake()->jobTitle(),
            'occupation_id' => Occupation::factory(),
            'destination_country_code' => fn () => CountryFactory::existingCode(),
            'destination_city' => fake()->city(),
            'headcount' => fake()->numberBetween(1, 200),
            'salary_amount_minor' => fake()->numberBetween(1500, 5000) * 100,
            'salary_currency' => 'AED',
            'salary_period' => SalaryPeriod::Month,
            'employment_type' => EmploymentType::FullTime,
            'salary_basis' => SalaryBasis::Gross,
            'working_hours' => WorkingHours::Day6To8,
            'working_days' => WorkingDays::Six,
            'contract_duration' => ContractDuration::Years2,
            'contract_months' => 24,
            'accommodation' => BenefitProvision::Provided,
            'permit_payer' => CostPayer::Employer,
            'visa_payer' => CostPayer::Employer,
            'deadline' => now()->addMonth(),
            'status' => Draft::class,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => Published::class, 'published_at' => now()]);
    }
}
