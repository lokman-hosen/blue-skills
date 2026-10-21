<?php

namespace App\Domain\Mobility\Support;

use App\Domain\Config\Enums\CountryRuleKey;
use App\Domain\Config\Models\CountryRule;

/**
 * The destination's mobility stages from its country rules (spec §6, product rule 8). A
 * destination without a template gets a short generic one so an accepted offer always opens a
 * case (D-078).
 */
final class StageTemplate
{
    /**
     * @var list<array{key: string, label: string}>
     */
    public const GENERIC = [
        ['key' => 'contract_accepted', 'label' => 'Contract accepted'],
        ['key' => 'medical', 'label' => 'Medical test'],
        ['key' => 'visa', 'label' => 'Visa'],
        ['key' => 'travel', 'label' => 'Travel'],
        ['key' => 'joined', 'label' => 'Joined'],
    ];

    /**
     * @return list<array{key: string, label: string, done_on_open?: bool}>
     */
    public static function for(string $countryCode): array
    {
        /** @var list<array{key: string, label: string, done_on_open?: bool}>|null $template */
        $template = CountryRule::query()
            ->where('country_code', $countryCode)
            ->where('key', CountryRuleKey::MobilityStageTemplate)
            ->value('value');

        return $template !== null && $template !== [] ? $template : self::GENERIC;
    }

    /**
     * A stage's name in the reader's language, falling back to the template's English label.
     */
    public static function label(string $countryCode, string $key): string
    {
        $translated = __("mobility.stage.{$key}");

        if ($translated !== "mobility.stage.{$key}") {
            return $translated;
        }

        $stage = collect(self::for($countryCode))->firstWhere('key', $key);

        return is_array($stage) ? $stage['label'] : str($key)->replace('_', ' ')->ucfirst()->toString();
    }
}
