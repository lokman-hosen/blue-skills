<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InvoiceLineRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:200'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'unit_amount' => ['required', 'decimal:0,3', 'min:0', 'max:10000000'],
            'credits' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ];
    }
}
