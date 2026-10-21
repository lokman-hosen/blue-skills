<?php

namespace App\Http\Requests\Organization;

use App\Domain\Applications\Support\ApplicationStages;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveApplicantsRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'application_ids' => ['required', 'array', 'min:1', 'max:200'],
            'application_ids.*' => ['string', 'ulid'],
            'stage' => ['required', Rule::in(ApplicationStages::MOVABLE)],
        ];
    }
}
