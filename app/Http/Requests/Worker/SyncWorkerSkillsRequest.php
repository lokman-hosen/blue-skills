<?php

namespace App\Http\Requests\Worker;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncWorkerSkillsRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'skill_ids' => ['present', 'array', 'max:30'],
            'skill_ids.*' => ['string', 'distinct', Rule::exists('skills', 'id')],
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
}
