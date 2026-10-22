<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Reference data is safe to run in every environment and on every deploy: it only adds
     * what is missing and never overwrites staff edits (D-108). The fictional sample world is
     * only seeded outside production.
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
