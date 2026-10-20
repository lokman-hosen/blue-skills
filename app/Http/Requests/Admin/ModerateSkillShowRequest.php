<?php

namespace App\Http\Requests\Admin;

use App\Domain\Worker\Enums\SkillShowRejectionReason;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModerateSkillShowRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['approve', 'reject'])],
            'reason' => ['required_if:decision,reject', 'nullable', Rule::enum(SkillShowRejectionReason::class)],
        ];
    }

    public function approves(): bool
    {
        return $this->string('decision')->toString() === 'approve';
    }

    public function reason(): ?SkillShowRejectionReason
    {
        return SkillShowRejectionReason::tryFrom($this->string('reason')->toString());
    }
}
