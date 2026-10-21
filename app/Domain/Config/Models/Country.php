<?php

namespace App\Domain\Config\Models;

use App\Domain\Config\Enums\CountryRuleKey;
use Database\Factories\CountryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'currency', 'dial_code', 'enabled_as_source', 'enabled_as_destination', 'legal_reviewed_at', 'legal_reviewed_by'])]
#[RouteKey('code')]
#[UseFactory(CountryFactory::class)]
class Country extends Model
{
    /** @use HasFactory<CountryFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled_as_source' => 'boolean',
            'enabled_as_destination' => 'boolean',
            'legal_reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<CountryRule, $this>
     */
    public function rules(): HasMany
    {
        return $this->hasMany(CountryRule::class, 'country_code', 'code');
    }

    public function rule(CountryRuleKey $key): mixed
    {
        return $this->rules->firstWhere('key', $key)?->value;
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function sources(Builder $query): void
    {
        $query->where('enabled_as_source', true);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function destinations(Builder $query): void
    {
        $query->where('enabled_as_destination', true);
    }
}
