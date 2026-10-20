<?php

namespace App\Http\Requests\Worker;

use App\Domain\Worker\Enums\LanguageProficiency;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncWorkerLanguagesRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'languages' => ['present', 'array', 'max:10'],
            'languages.*.code' => ['required', 'string', 'distinct', Rule::exists('languages', 'code')],
            'languages.*.proficiency' => ['required', Rule::enum(LanguageProficiency::class)],
        ];
    }

    /**
     * @return list<array{code: string, proficiency: string}>
     */
    public function languages(): array
    {
        /** @var list<array{code: string, proficiency: string}> $languages */
        $languages = array_values($this->validated('languages', []));

        return $languages;
    }
}
