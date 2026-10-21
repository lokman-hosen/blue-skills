<?php

namespace App\Domain\Taxonomy\Support;

use App\Domain\Taxonomy\Models\Occupation;
use App\Domain\Taxonomy\Models\Sector;
use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Taxonomy\Models\Specialization;
use Illuminate\Support\Facades\DB;

/**
 * How many records point at a taxonomy item. Only unused items can be removed (D-093).
 */
final class TaxonomyUsage
{
    public static function count(Sector|Occupation|Specialization|Skill $item): int
    {
        return match (true) {
            $item instanceof Sector => $item->occupations()->count(),
            $item instanceof Occupation => DB::table('worker_profiles')->where('occupation_id', $item->id)->count()
                + DB::table('jobs')->where('occupation_id', $item->id)->count()
                + DB::table('courses')->where('occupation_id', $item->id)->count()
                + DB::table('talent_pools')->where('occupation_id', $item->id)->count()
                + DB::table('job_mandates')->whereJsonContains('occupation_ids', $item->id)->count()
                + $item->specializations()->count()
                + $item->skills()->count(),
            $item instanceof Specialization => DB::table('worker_profiles')->where('specialization_id', $item->id)->count(),
            $item instanceof Skill => DB::table('worker_skills')->where('skill_id', $item->id)->count()
                + DB::table('job_skill_requirements')->where('skill_id', $item->id)->count()
                + DB::table('skill_show_skill')->where('skill_id', $item->id)->count(),
        };
    }
}
