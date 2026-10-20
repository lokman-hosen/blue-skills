<?php

namespace App\Domain\Config\Data;

use App\Domain\Config\Models\Country;
use Spatie\LaravelData\Data;

class CountryOptionData extends Data
{
    public function __construct(
        public string $code,
        public string $name,
        public string $dialCode,
    ) {}

    public static function fromCountry(Country $country): self
    {
        return new self($country->code, $country->name, $country->dial_code);
    }
}
