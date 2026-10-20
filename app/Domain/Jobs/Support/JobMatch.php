<?php

namespace App\Domain\Jobs\Support;

use App\Domain\Jobs\Data\MatchRequirementData;
use App\Domain\Jobs\Data\MatchSummaryData;
use App\Domain\Jobs\Models\JobPosting;
use App\Domain\Taxonomy\Models\Skill;
use App\Domain\Worker\Enums\PassportStatus;
use App\Domain\Worker\Models\WorkerProfile;

/**
 * Rules-based matching from the worker's confirmed profile (spec §8, product rule 12): each
 * requirement is listed as met or not, with no score, weighting or video signal.
 */
final class JobMatch
{
    public static function explain(WorkerProfile $profile, JobPosting $job): MatchSummaryData
    {
        $profile->loadMissing(['workerSkills', 'languages']);
        $job->loadMissing(['skills', 'occupation']);

        $requirements = [
            new MatchRequirementData('occupation', ['occupation' => $job->occupation?->translatedName() ?? ''], $profile->occupation_id === $job->occupation_id),
        ];

        if ($job->min_experience_years !== null) {
            $requirements[] = new MatchRequirementData(
                'experience',
                ['years' => $job->min_experience_years],
                ($profile->years_experience ?? 0) >= $job->min_experience_years,
            );
        }

        $workerSkills = $profile->workerSkills->pluck('skill_id')->all();

        foreach ($job->skills as $skill) {
            /** @var Skill $skill */
            $requirements[] = new MatchRequirementData('skill', ['skill' => $skill->translatedName()], in_array($skill->id, $workerSkills, true));
        }

        $workerLanguages = $profile->languages->pluck('language_code')->all();

        foreach ($job->language_requirements ?? [] as $language) {
            $requirements[] = new MatchRequirementData(
                'language',
                ['language' => (string) $language['code'], 'level' => (string) $language['level']],
                in_array($language['code'], $workerLanguages, true),
            );
        }

        $requirements[] = new MatchRequirementData('passport', [], $profile->passport_status === PassportStatus::Ready);

        return new MatchSummaryData(
            met: count(array_filter($requirements, fn (MatchRequirementData $requirement) => $requirement->met)),
            total: count($requirements),
            requirements: $requirements,
        );
    }
}
