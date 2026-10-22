<?php

namespace App\Http\Requests\Admin;

use App\Domain\Crm\Enums\FollowUpStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'assigned_to' => ['nullable', 'string', 'exists:users,id'],
            'follow_up_status' => ['required', Rule::enum(FollowUpStatus::class)],
            'next_follow_up_at' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}
