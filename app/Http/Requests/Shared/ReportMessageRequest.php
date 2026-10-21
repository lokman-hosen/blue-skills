<?php

namespace App\Http\Requests\Shared;

use App\Domain\Messaging\Actions\ReportMessage;
use App\Domain\Trust\Enums\ReportCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportMessageRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in(array_map(fn (ReportCategory $category) => $category->value, ReportMessage::CATEGORIES))],
            'description' => ['nullable', 'required_if:category,other', 'string', 'max:500'],
        ];
    }
}
