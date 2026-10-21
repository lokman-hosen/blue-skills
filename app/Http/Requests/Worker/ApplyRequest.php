<?php

namespace App\Http\Requests\Worker;

use App\Domain\Applications\Enums\ApplicationConsentItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplyRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items' => ['present', 'array'],
            'items.*' => ['string', Rule::enum(ApplicationConsentItem::class)],
        ];
    }

    /**
     * @return list<ApplicationConsentItem>
     */
    public function items(): array
    {
        /** @var list<string> $items */
        $items = $this->array('items');

        return array_map(fn (string $item) => ApplicationConsentItem::from($item), $items);
    }
}
