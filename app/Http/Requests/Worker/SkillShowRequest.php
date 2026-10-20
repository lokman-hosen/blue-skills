<?php

namespace App\Http\Requests\Worker;

use App\Domain\Worker\Enums\ProfileVisibility;
use App\Domain\Worker\Models\SkillShow;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A new SkillShow (with `video`) or new tags and visibility for the existing one.
 */
class SkillShowRequest extends FormRequest
{
    public const MAX_SKILLS = 6;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'video' => [
                Rule::requiredIf($this->isMethod('post')), 'file',
                'mimetypes:'.implode(',', SkillShow::ACCEPTED_MIME_TYPES),
                'max:'.SkillShow::MAX_UPLOAD_KB,
            ],
            'skill_ids' => ['required', 'array', 'min:1', 'max:'.self::MAX_SKILLS],
            'skill_ids.*' => ['string', 'distinct', Rule::exists('skills', 'id')],
            'visibility' => ['required', Rule::enum(ProfileVisibility::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'video.mimetypes' => __('skillshow.error.type'),
            'video.max' => __('skillshow.error.size', ['max' => SkillShow::MAX_UPLOAD_KB / 1024]),
            'skill_ids.required' => __('skillshow.error.skills'),
            'skill_ids.min' => __('skillshow.error.skills'),
            'skill_ids.max' => __('skillshow.error.skills_max', ['max' => self::MAX_SKILLS]),
        ];
    }

    /**
     * @return list<string>
     */
    public function skillIds(): array
    {
        /** @var list<string> $ids */
        $ids = array_values($this->validated('skill_ids', []));

        return $ids;
    }

    public function visibility(): ProfileVisibility
    {
        return ProfileVisibility::from($this->string('visibility')->toString());
    }
}
