<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Enums\OtpPurpose;
use App\Domain\Identity\Models\OtpChallenge;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VerifyPhoneOtp
{
    /**
     * Checks the code and consumes the challenge. Every wrong guess counts against the limit,
     * so the attempt is committed before any error is raised.
     *
     * @return string The verified phone number (E.164).
     *
     * @throws ValidationException
     */
    public function handle(string $challengeId, string $code, OtpPurpose $purpose = OtpPurpose::Login): string
    {
        /** @var array{status: 'ok'|'expired'|'wrong', phone?: string, remaining?: int} $result */
        $result = DB::transaction(function () use ($challengeId, $code, $purpose): array {
            $challenge = OtpChallenge::query()->lockForUpdate()->find($challengeId);

            // A code only works for what it was sent for.
            if ($challenge === null || $challenge->purpose !== $purpose || ! $challenge->isUsable()) {
                return ['status' => 'expired'];
            }

            $challenge->increment('attempts');

            if (! hash_equals($challenge->code_hash, OtpChallenge::hashCode($code))) {
                return ['status' => 'wrong', 'remaining' => (int) config('blueskills.otp.max_attempts') - $challenge->attempts];
            }

            $challenge->forceFill(['consumed_at' => now()])->save();

            return ['status' => 'ok', 'phone' => $challenge->phone];
        });

        if ($result['status'] === 'ok' && isset($result['phone'])) {
            return $result['phone'];
        }

        $remaining = $result['remaining'] ?? 0;

        throw ValidationException::withMessages([
            'code' => $remaining > 0 ? __('auth.otp.invalid', ['remaining' => $remaining]) : __('auth.otp.expired'),
        ]);
    }
}
