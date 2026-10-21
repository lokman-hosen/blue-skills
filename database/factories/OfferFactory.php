<?php

namespace Database\Factories;

use App\Domain\Applications\Enums\OfferStatus;
use App\Domain\Applications\Models\Application;
use App\Domain\Applications\Models\Offer;
use App\Domain\Jobs\Enums\SalaryPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Offer>
 */
class OfferFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'salary_amount_minor' => 2_200_00,
            'salary_currency' => 'AED',
            'salary_period' => SalaryPeriod::Month,
            'start_on' => now()->addMonths(2)->toDateString(),
            'respond_by' => now()->addDays(7)->toDateString(),
            'status' => OfferStatus::Sent,
        ];
    }
}
