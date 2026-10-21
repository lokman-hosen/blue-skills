<?php

namespace App\Domain\Jobs\Data;

use App\Domain\Config\Models\Country;
use App\Domain\Jobs\Enums\JobCostCategory;
use App\Domain\Jobs\Support\JobCompliance;
use App\Domain\Jobs\Support\WorkerCostRules;
use Spatie\LaravelData\Data;

/**
 * A destination with the rules the wizard shows while the employer fills in the job.
 */
class DestinationOptionData extends Data
{
    /**
     * @param  list<JobCostCategory>  $allowedWorkerCosts
     * @param  list<string>  $mandatoryFields
     */
    public function __construct(
        public string $code,
        public string $name,
        public string $currency,
        public array $allowedWorkerCosts,
        public ?string $workerCostPolicy,
        public array $mandatoryFields,
    ) {}

    public static function fromCountry(Country $country): self
    {
        return new self(
            code: $country->code,
            name: $country->name,
            currency: $country->currency,
            allowedWorkerCosts: WorkerCostRules::allowedCategories($country->code),
            workerCostPolicy: WorkerCostRules::policyText($country->code),
            mandatoryFields: JobCompliance::mandatoryFields($country->code),
        );
    }
}
