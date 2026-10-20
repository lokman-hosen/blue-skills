<?php

namespace App\Domain\Config\Models;

use App\Domain\Config\Enums\CountryRuleKey;
use Database\Factories\CountryRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Country configuration (spec §12): worker-cost rules, mandatory disclosures, document
 * types, permit/visa milestone templates and local notices.
 */
#[Fillable(['country_code', 'key', 'value'])]
#[UseFactory(CountryRuleFactory::class)]
class CountryRule extends Model
{
    /** @use HasFactory<CountryRuleFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'key' => CountryRuleKey::class,
            'value' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }
}
