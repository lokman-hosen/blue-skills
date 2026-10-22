<?php

namespace App\Http\Requests\Admin;

use App\Domain\Crm\Enums\ContactChannel;
use App\Domain\Crm\Enums\ContactOutcome;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LogContactAttemptRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'channel' => ['required', Rule::enum(ContactChannel::class)],
            'outcome' => ['required', Rule::enum(ContactOutcome::class)],
            'note' => ['nullable', 'string', 'max:1000'],
            'next_follow_up_at' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
