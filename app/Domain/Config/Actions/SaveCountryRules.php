<?php

namespace App\Domain\Config\Actions;

use App\Domain\Config\Enums\CountryRuleKey;
use App\Domain\Config\Models\Country;
use App\Domain\Config\Models\CountryRule;
use App\Domain\Config\Support\CountryRules;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Saves a corridor's rules (D-093). Each changed rule is written to the activity log with its
 * old and new value. Stage changes apply to cases opened from now on; existing cases keep their
 * stages.
 *
 * @phpstan-type Stage array{key: string, label: string, done_on_open: bool}
 */
class SaveCountryRules
{
    /**
     * @param  array<string, mixed>  $rules  As CountryRules::for() returns them.
     */
    public function handle(Country $country, array $rules, User $actor): void
    {
        $before = CountryRules::for($country);

        DB::transaction(function () use ($country, $rules, $before, $actor): void {
            foreach (CountryRuleKey::cases() as $key) {
                if (! array_key_exists($key->value, $rules) || $rules[$key->value] === $before[$key->value]) {
                    continue;
                }

                $value = $rules[$key->value];
                $stored = match (true) {
                    in_array($key, CountryRules::TEXT_KEYS, true) => ['text' => (string) $value],
                    $key === CountryRuleKey::MobilityStageTemplate => self::stages(is_array($value) ? $value : []),
                    default => array_values(array_unique(is_array($value) ? $value : [])),
                };

                CountryRule::query()->updateOrCreate(['country_code' => $country->code, 'key' => $key], ['value' => $stored]);

                activity('config')->performedOn($country)->causedBy($actor)
                    ->withProperties(['rule' => $key->value, 'old' => $before[$key->value], 'attributes' => $value])
                    ->log('country.rule_updated');
            }
        });
    }

    /**
     * New stages get a key from their label; existing keys stay so open cases keep matching.
     *
     * @param  array<int, mixed>  $stages
     * @return list<array<string, mixed>>
     */
    private static function stages(array $stages): array
    {
        $rows = [];
        $used = [];

        foreach ($stages as $stage) {
            if (! is_array($stage) || trim((string) ($stage['label'] ?? '')) === '') {
                continue;
            }

            $key = (string) ($stage['key'] ?? '') !== '' ? (string) $stage['key'] : str((string) $stage['label'])->slug('_')->limit(48, '')->toString();

            while (in_array($key, $used, true)) {
                $key .= '_2';
            }

            $used[] = $key;
            $row = ['key' => $key, 'label' => trim((string) $stage['label'])];

            if (($stage['done_on_open'] ?? false) === true) {
                $row['done_on_open'] = true;
            }

            $rows[] = $row;
        }

        return $rows;
    }
}
