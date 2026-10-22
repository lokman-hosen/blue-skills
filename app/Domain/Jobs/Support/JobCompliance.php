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
 * filled in, every worker-paid cost is allowed on the corridor (D-053, product rule 13) and an
 * agency job is backed by a usable mandate that covers it (product rule 10).
 */
final class JobCompliance
{
    /**
     * Country-rule disclosure keys → the job field that discloses them.
     */
    /** Disclosure keys a corridor can require, and the job field each one needs (D-093). */
    public const DISCLOSURES = [
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
        $problems = self::mandateProblems($job);
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

    /**
     * An agency job needs a verified, in-date mandate from its employer, under a licence valid
     * for the destination, covering the trade, destination and headcount (D-077).
     *
     * @return array<string, string>
     */
    public static function mandateProblems(JobPosting $job): array
    {
        if ($job->agency_organization_id === null) {
            return [];
        }

        $mandate = $job->loadMissing('mandate.agency')->mandate;

        if ($mandate === null) {
            return ['job_mandate_id' => __('jobs.mandate.missing')];
        }

        if (! $mandate->isUsable() || $mandate->agency_organization_id !== $job->agency_organization_id) {
            return ['job_mandate_id' => __('jobs.mandate.not_usable', ['reference' => $mandate->reference])];
        }

        if (! $mandate->covers($job->occupation_id, $job->destination_country_code)) {
            return ['job_mandate_id' => __('jobs.mandate.out_of_scope', ['reference' => $mandate->reference])];
        }

        $left = $mandate->headcountLeft($job);

        return $job->headcount > $left
            ? ['headcount' => __('jobs.mandate.headcount', ['reference' => $mandate->reference, 'left' => $left])]
            : [];
    }
}
