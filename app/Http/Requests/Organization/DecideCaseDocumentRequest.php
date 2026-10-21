<?php

namespace App\Http\Requests\Organization;

use App\Domain\Mobility\Enums\CaseDocumentReturnReason;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideCaseDocumentRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // A returned file says why; "other" needs the words.
        $returning = $this->route('decision') === 'return';

        return [
            'reason' => [$returning ? 'required' : 'nullable', Rule::enum(CaseDocumentReturnReason::class)],
            'note' => [$returning && $this->input('reason') === CaseDocumentReturnReason::Other->value ? 'required' : 'nullable', 'string', 'max:500'],
        ];
    }
}
