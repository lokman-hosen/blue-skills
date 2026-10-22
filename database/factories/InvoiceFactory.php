<?php

namespace Database\Factories;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\States\Invoice\Draft;
use App\Domain\Billing\States\Invoice\Issued;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory()->employer()->verified(),
            'status' => Draft::class,
            'currency' => 'AED',
            'tax_rate_bp' => 500,
        ];
    }

    public function issued(): static
    {
        return $this->state(fn () => [
            'status' => Issued::class,
            'number' => Invoice::newNumber(),
            'issued_at' => now(),
            'due_on' => now()->addDays(14)->toDateString(),
        ]);
    }
}
