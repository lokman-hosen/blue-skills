<?php

namespace App\Domain\Config\Support;

use App\Domain\Config\Enums\CountryRuleKey;
use App\Domain\Config\Models\Country;
use App\Domain\Config\Models\CountryRule;

/**
 * Reads a country's rules into the shapes the configuration editor uses (D-093): lists for
 * languages, cost categories, disclosures and document types; text for policies and notices;
 * stage rows for the mobility template.
 */
final class CountryRules
{
    /** Rules stored as `{text: "…"}`. */
    public const TEXT_KEYS = [CountryRuleKey::AgencyEligibility, CountryRuleKey::WorkerCostPolicy, CountryRuleKey::LocalNotice];

    /** Rules stored as a list of codes. */
    public const LIST_KEYS = [CountryRuleKey::Languages, CountryRuleKey::AllowedWorkerCostCategories, CountryRuleKey::MandatoryDisclosures, CountryRuleKey::DocumentTypes];

    /**
     * @return array<string, mixed>
     */
    public static function for(Country $country): array
    {
        $stored = CountryRule::query()->where('country_code', $country->code)->get()
            ->mapWithKeys(fn (CountryRule $rule) => [$rule->key->value => $rule->value])->all();

        $rules = [];

        foreach (self::TEXT_KEYS as $key) {
            $rules[$key->value] = (string) ($stored[$key->value]['text'] ?? '');
        }

        foreach (self::LIST_KEYS as $key) {
            $rules[$key->value] = array_values(array_map('strval', $stored[$key->value] ?? []));
        }

        $stages = $stored[CountryRuleKey::MobilityStageTemplate->value] ?? [];
        $rules[CountryRuleKey::MobilityStageTemplate->value] = array_values(array_map(fn ($stage) => [
            'key' => (string) ($stage['key'] ?? ''),
            'label' => (string) ($stage['label'] ?? ''),
            'done_on_open' => (bool) ($stage['done_on_open'] ?? false),
        ], array_filter($stages, 'is_array')));

        return $rules;
    }
}
