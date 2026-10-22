<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Reference data is safe to run in every environment (idempotent). The fictional
     * sample world is only seeded outside production.
     */
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            CountryRuleSeeder::class,
            LanguageSeeder::class,
            TaxonomySeeder::class,
            RolesAndPermissionsSeeder::class,
            SuperuserSeeder::class,
        ]);

        if (! app()->isProduction()) {
            $this->call([SampleWorldSeeder::class, CrmAndBillingSampleSeeder::class]);
        }
    }
}
