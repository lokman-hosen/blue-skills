<?php

namespace Database\Seeders;

use App\Domain\Config\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Launch corridors: South Asian source countries and GCC/EU/East Asia destinations.
     */
    public function run(): void
    {
        $countries = [
            ['BD', 'Bangladesh', 'BDT', '+880', true, false],
            ['NP', 'Nepal', 'NPR', '+977', true, false],
            ['IN', 'India', 'INR', '+91', true, false],
            ['PK', 'Pakistan', 'PKR', '+92', true, false],
            ['LK', 'Sri Lanka', 'LKR', '+94', true, false],
            ['AE', 'United Arab Emirates', 'AED', '+971', false, true],
            ['SA', 'Saudi Arabia', 'SAR', '+966', false, true],
            ['QA', 'Qatar', 'QAR', '+974', false, true],
            ['OM', 'Oman', 'OMR', '+968', false, true],
            ['PL', 'Poland', 'PLN', '+48', false, true],
            ['JP', 'Japan', 'JPY', '+81', false, true],
            ['CZ', 'Czechia', 'CZK', '+420', false, true],
        ];

        foreach ($countries as [$code, $name, $currency, $dialCode, $source, $destination]) {
            Country::query()->updateOrCreate(['code' => $code], [
                'name' => $name,
                'currency' => $currency,
                'dial_code' => $dialCode,
                'enabled_as_source' => $source,
                'enabled_as_destination' => $destination,
            ]);
        }
    }
}
