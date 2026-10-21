<?php

namespace App\Domain\Institute\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Institute\Enums\CohortStatus;
use App\Domain\Institute\Models\Cohort;
use App\Domain\Institute\Models\Course;

/**
 * Creates a cohort of a course with its own invite code, or edits its name and dates.
 */
class SaveCohort
{
    /**
     * @param  array{name: string, starts_on: string|null, ends_on: string|null}  $data
     */
    public function handle(Course $course, ?Cohort $cohort, array $data, User $actor): Cohort
    {
        $creating = $cohort === null;
        $cohort ??= new Cohort([
            'organization_id' => $course->organization_id,
            'course_id' => $course->id,
            'invite_code' => Cohort::newInviteCode(),
            'status' => CohortStatus::Open,
            'created_by' => $actor->id,
        ]);
        $old = $creating ? [] : ['name' => $cohort->name, 'starts_on' => $cohort->starts_on?->toDateString(), 'ends_on' => $cohort->ends_on?->toDateString()];
        $cohort->fill($data)->save();

        activity('institute')->performedOn($cohort)->causedBy($actor)
            ->withProperties(['old' => $old, 'attributes' => $data])
            ->log($creating ? 'cohort.created' : 'cohort.updated');

        return $cohort;
    }
}
