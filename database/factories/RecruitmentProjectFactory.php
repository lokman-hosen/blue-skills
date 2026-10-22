<?php

namespace Database\Factories;

use App\Domain\Jobs\Models\RecruitmentProject;
use App\Domain\Jobs\States\RecruitmentProject\Active;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecruitmentProject>
 */
class RecruitmentProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory()->employer()->verified(),
            'reference' => fn (array $attributes) => RecruitmentProject::newReference((string) $attributes['organization_id']),
            'name' => '200 Electricians — Dubai',
            'target_headcount' => 200,
            'destination_country_code' => 'AE',
            'source_country_codes' => ['BD', 'NP', 'IN'],
            'status' => Active::class,
            'target_date' => now()->addMonths(4)->toDateString(),
        ];
    }
}
