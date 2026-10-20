<?php

namespace Database\Seeders;

use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Sector;
use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Taxonomy\Models\Specialization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Sample taxonomy from the prototype. English only: other locales need human
 * translation (see docs/translation-todo.md).
 */
class TaxonomySeeder extends Seeder
{
    /**
     * sector => [occupation => [specializations[], skills[]]]
     *
     * @var array<string, array<string, array{0: list<string>, 1: list<string>}>>
     */
    private const TREE = [
        'Construction & MEP' => [
            'Electrician' => [
                ['Industrial electrician', 'Building electrician'],
                ['Industrial wiring', 'DB installation', 'Cable termination', 'Troubleshooting', 'Conduit bending'],
            ],
            'Electrical technician' => [['Panel technician'], ['Panel wiring', 'PLC basics']],
            'HVAC technician' => [
                ['Split systems', 'Chillers'],
                ['Refrigerant handling', 'Split AC installation', 'Chiller maintenance'],
            ],
            'Plumber' => [[], []],
            'Mason' => [[], []],
            'Carpenter' => [[], []],
            'Painter' => [[], []],
            'Steel fixer' => [[], []],
        ],
        'Logistics' => [
            'Warehouse operative' => [['Forklift operator'], ['Picking & packing', 'Forklift (UDT)', 'Scanner use']],
        ],
        'Manufacturing' => [
            'Welder' => [['MIG/MAG', 'TIG'], ['Fillet welds', 'Blueprint reading', 'MIG/MAG 135/136']],
        ],
        'Transport' => [
            'Driver' => [['Heavy vehicle driver', 'Light vehicle driver'], []],
        ],
    ];

    /**
     * Skills used across occupations.
     */
    private const SHARED_SKILLS = ['Maintenance'];

    public function run(): void
    {
        $order = 0;

        foreach (self::TREE as $sectorName => $occupations) {
            $sector = Sector::query()->updateOrCreate(
                ['slug' => Str::slug($sectorName)],
                ['name' => ['en' => $sectorName], 'sort_order' => $order++],
            );

            foreach ($occupations as $occupationName => [$specializations, $skills]) {
                $occupation = Occupation::query()->updateOrCreate(
                    ['slug' => Str::slug($occupationName)],
                    ['sector_id' => $sector->id, 'name' => ['en' => $occupationName]],
                );

                foreach ($specializations as $name) {
                    Specialization::query()->updateOrCreate(
                        ['occupation_id' => $occupation->id, 'slug' => Str::slug($name)],
                        ['name' => ['en' => $name]],
                    );
                }

                foreach ($skills as $name) {
                    Skill::query()->updateOrCreate(
                        ['slug' => Str::slug($name)],
                        ['occupation_id' => $occupation->id, 'name' => ['en' => $name]],
                    );
                }
            }
        }

        foreach (self::SHARED_SKILLS as $name) {
            Skill::query()->updateOrCreate(['slug' => Str::slug($name)], ['occupation_id' => null, 'name' => ['en' => $name]]);
        }
    }
}
