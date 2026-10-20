<?php

namespace App\Domain\Jobs\Support;

use App\Domain\Config\Enums\CountryRuleKey;
use App\Domain\Config\Models\CountryRule;
use App\Domain\Jobs\Enums\CostPayer;
use App\Domain\Jobs\Enums\JobCostCategory;
use App\Domain\Jobs\Models\JobCost;
use App\Domain\Jobs\Models\JobPosting;

/**
 * What must be true before a job goes to review: the destination's mandatory disclosures are
 * filled in, and every worker-paid cost is allowed on the corridor (D-053, product rule 13).
 */
final class JobCompliance
{
    /**
     * Country-rule disclosure keys → the job field that discloses them.
     */
    private const DISCLOSURES = [
        'salary' => 'salary_amount_minor',
        'gross_pay' => 'salary_amount_minor',
        'monthly_pay' => 'salary_amount_minor',
        'accommodation' => 'accommodation',
        'accommodation_cost' => 'accommodation',
        'dormitory_cost' => 'accommodation',
        'hostel_deduction' => 'accommodation',
        'food' => 'food',
        'working_hours' => 'hours_per_day',
        'shift_pattern' => 'hours_per_day',
        'overtime' => 'overtime_terms',
        'contract_length' => 'contract_months',
        'contract_type' => 'contract_months',
    ];

    /**
     * @return array<string, string> Field or `costs` → message, empty when the job may be submitted.
     */
    public static function problems(JobPosting $job): array
    {
        $job->loadMissing(['costs', 'destinationCountry']);
        $problems = [];
        $country = $job->destinationCountry->name ?? $job->destination_country_code;

        foreach (self::mandatoryFields($job->destination_country_code) as $field) {
            if ($job->getAttribute($field) === null || $job->getAttribute($field) === '') {
                $problems[$field] = __('jobs.compliance.mandatory', ['country' => $country]);
            }
        }

        $allowed = WorkerCostRules::allowedCategories($job->destination_country_code);
        $workerPays = $job->costs
            ->filter(fn (JobCost $cost) => $cost->payer === CostPayer::Worker)
            ->map(fn (JobCost $cost) => $cost->category)
            ->values()
            ->all();

        if ($job->visa_payer === CostPayer::Worker) {
            $workerPays[] = JobCostCategory::Visa;
        }

        if ($job->permit_payer === CostPayer::Worker) {
            $workerPays[] = JobCostCategory::WorkPermit;
        }

        $unlawful = array_values(array_unique(array_map(
            fn (JobCostCategory $category) => $category->value,
            array_filter($workerPays, fn (JobCostCategory $category) => ! in_array($category, $allowed, true)),
        )));

        if ($unlawful !== []) {
            $problems['costs'] = __('jobs.compliance.worker_cost', [
                'country' => $country,
                'categories' => implode(', ', array_map(fn (string $category) => __("truth_card.cost.{$category}"), $unlawful)),
                'policy' => WorkerCostRules::policyText($job->destination_country_code) ?? __('jobs.compliance.no_rules'),
            ]);
        }

        return $problems;
    }

    /**
     * @return list<string>
     */
    public static function mandatoryFields(string $countryCode): array
    {
        /** @var list<string>|null $keys */
        $keys = CountryRule::query()
            ->where('country_code', $countryCode)
            ->where('key', CountryRuleKey::MandatoryDisclosures)
            ->value('value');

        return array_values(array_unique(array_filter(array_map(fn (string $key) => self::DISCLOSURES[$key] ?? null, $keys ?? []))));
    }
}
