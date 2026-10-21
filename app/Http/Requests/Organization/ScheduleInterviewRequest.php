<?php

namespace App\Http\Requests\Organization;

use App\Domain\Applications\Enums\InterviewMode;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ScheduleInterviewRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Local time in the scheduler's own time zone, from a datetime-local input.
            'scheduled_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:240'],
            'mode' => ['required', Rule::enum(InterviewMode::class)],
            'location' => ['nullable', 'required_if:mode,in_person', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'location.required_if' => __('applications.interview.location_required'),
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $validator->errors()->has('scheduled_at') && ! $this->scheduledAt()->isFuture()) {
                    $validator->errors()->add('scheduled_at', __('applications.interview.in_past'));
                }
            },
        ];
    }

    public function scheduledAt(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d\TH:i', $this->string('scheduled_at')->toString(), $this->user()->timezone ?? 'UTC')
            ->utc();
    }

    /**
     * @return array{scheduled_at: CarbonImmutable, duration_minutes: int, mode: InterviewMode, location: ?string, instructions: ?string}
     */
    public function details(): array
    {
        return [
            'scheduled_at' => $this->scheduledAt(),
            'duration_minutes' => $this->integer('duration_minutes'),
            'mode' => InterviewMode::from($this->string('mode')->toString()),
            'location' => $this->filled('location') ? $this->string('location')->trim()->toString() : null,
            'instructions' => $this->filled('instructions') ? $this->string('instructions')->trim()->toString() : null,
        ];
    }
}
