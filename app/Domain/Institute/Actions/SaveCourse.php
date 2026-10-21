<?php

namespace App\Domain\Institute\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\Institute\Models\Course;
use App\Domain\Organization\Models\Organization;

/**
 * Creates or edits a course. Attestations keep the title they were issued with (D-083).
 */
class SaveCourse
{
    /**
     * @param  array{title: string, occupation_id: string|null, duration_weeks: int|null, description: string|null}  $data
     */
    public function handle(Organization $institute, ?Course $course, array $data, User $actor): Course
    {
        $course ??= new Course(['organization_id' => $institute->id]);
        $old = $course->exists ? $course->only(array_keys($data)) : [];
        $course->fill($data)->save();

        activity('institute')->performedOn($course)->causedBy($actor)
            ->withProperties(['old' => $old, 'attributes' => $data])
            ->log($old === [] ? 'course.created' : 'course.updated');

        return $course;
    }
}
