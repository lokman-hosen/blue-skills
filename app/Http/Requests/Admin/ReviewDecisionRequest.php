<?php

namespace App\Http\Requests\Admin;

use App\Domain\Verification\Enums\ReviewReason;
use App\Domain\Verification\Enums\VerificationDecision;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A staff decision on an organization or job review: approve, ask for more, or reject.
 */
class ReviewDecisionRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::enum(VerificationDecision::class)],
            'reason' => ['required_unless:decision,approved', 'nullable', Rule::enum(ReviewReason::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['reason.required_unless' => __('admin.review.reason_required')];
    }

    public function decision(): VerificationDecision
    {
        return VerificationDecision::from($this->string('decision')->toString());
    }

    public function reason(): ?ReviewReason
    {
        return ReviewReason::tryFrom($this->string('reason')->toString());
    }

    public function note(): ?string
    {
        return $this->filled('note') ? $this->string('note')->trim()->toString() : null;
    }
}
