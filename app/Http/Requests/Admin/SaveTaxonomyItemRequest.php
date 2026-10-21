<?php

namespace App\Http\Requests\Admin;

use App\Domain\Taxonomy\Actions\SaveTaxonomyItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTaxonomyItemRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = $this->route('type') ?? $this->input('type');
        $parentTable = match ($type) {
            'occupation' => 'sectors',
            'specialization', 'skill' => 'occupations',
            default => null,
        };

        return [
            'type' => [$this->isMethod('post') ? 'required' : 'nullable', Rule::in(SaveTaxonomyItem::TYPES)],
            'parent_id' => [$this->isMethod('post') && $parentTable !== null ? 'required' : 'nullable', 'string', ...($parentTable !== null ? ["exists:{$parentTable},id"] : [])],
            'names' => ['required', 'array'],
            'names.en' => ['required', 'string', 'max:120'],
            'names.*' => ['nullable', 'string', 'max:120'],
        ];
    }
}
