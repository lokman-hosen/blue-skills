<?php

namespace App\Domain\Jobs\Support;

use App\Domain\Config\Enums\CountryRuleKey;
use App\Domain\Config\Models\CountryRule;
use App\Domain\Jobs\Enums\JobCostCategory;

/**
 * Which costs a worker may lawfully pay for jobs in a destination country (product rule 13).
 * Read from country rules, never hard-coded. A country without rules allows none.
 */
final class WorkerCostRules
{
    /**
     * @return list<JobCostCategory>
     */
    public static function allowedCategories(string $countryCode): array
    {
        /** @var list<string>|null $value */
        $value = CountryRule::query()
            ->where('country_code', $countryCode)
            ->where('key', CountryRuleKey::AllowedWorkerCostCategories)
            ->value('value');

        return array_values(array_filter(array_map(fn (string $category) => JobCostCategory::tryFrom($category), $value ?? [])));
    }

    public static function policyText(string $countryCode): ?string
    {
        /** @var array{text?: string}|null $value */
        $value = CountryRule::query()
            ->where('country_code', $countryCode)
            ->where('key', CountryRuleKey::WorkerCostPolicy)
            ->value('value');

        return $value['text'] ?? null;
    }

    public static function allows(string $countryCode, JobCostCategory $category): bool
    {
        return in_array($category, self::allowedCategories($countryCode), true);
    }
}
