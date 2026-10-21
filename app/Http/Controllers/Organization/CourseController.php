<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Institute\Actions\SaveCourse;
use App\Domain\Institute\Data\CourseData;
use App\Domain\Institute\Models\Course;
use App\Domain\Taxonomy\Data\TaxonomyOptionData;
use App\Domain\Taxonomy\Models\Occupation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\SaveCourseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The institute's courses and their cohorts (spec §7, prototype i-cohort).
 */
class CourseController extends Controller
{
    use ResolvesCurrentOrganization;

    public function index(Request $request): Response
    {
        $institute = $this->organization($request);
        Gate::authorize('viewAny', [Course::class, $institute]);

        $courses = $institute->courses()->with(['occupation', 'cohorts.members'])->orderBy('title')->get();

        return Inertia::render('org/courses/index', [
            'courses' => $courses->map(fn (Course $course) => CourseData::fromCourse($course))->values()->all(),
            'occupations' => Occupation::query()->orderBy('slug')->get()->map(fn (Occupation $occupation) => TaxonomyOptionData::fromModel($occupation))->values()->all(),
            'canManage' => Gate::allows('create', [Course::class, $institute]),
        ]);
    }

    public function store(SaveCourseRequest $request, SaveCourse $save): RedirectResponse
    {
        $institute = $this->organization($request);
        Gate::authorize('create', [Course::class, $institute]);

        $save->handle($institute, null, $this->fields($request), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('courses.created')]);

        return to_route('org.courses.index');
    }

    public function update(SaveCourseRequest $request, Course $course, SaveCourse $save): RedirectResponse
    {
        $institute = $this->organization($request);
        abort_unless($course->organization_id === $institute->id, 404);
        Gate::authorize('update', $course);

        $save->handle($institute, $course, $this->fields($request), $request->user());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('courses.saved')]);

        return to_route('org.courses.index');
    }

    /**
     * @return array{title: string, occupation_id: string|null, duration_weeks: int|null, description: string|null}
     */
    private function fields(SaveCourseRequest $request): array
    {
        return [
            'title' => $request->string('title')->trim()->toString(),
            'occupation_id' => $request->string('occupation_id')->toString() ?: null,
            'duration_weeks' => $request->filled('duration_weeks') ? $request->integer('duration_weeks') : null,
            'description' => $request->string('description')->trim()->toString() ?: null,
        ];
    }
}
