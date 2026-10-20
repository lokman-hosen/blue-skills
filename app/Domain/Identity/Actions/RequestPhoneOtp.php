<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Contracts\OtpChannel;
use App\Domain\Identity\Enums\OtpPurpose;
use App\Domain\Identity\Exceptions\PhoneSignInBlocked;
use App\Domain\Identity\Jobs\SendOtpCode;
use App\Domain\Identity\Models\OtpChallenge;
use App\Domain\Identity\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Issues a one-time code for a worker phone (sign-in, or confirming account deletion) and
 * queues its delivery. Limits are counted from stored challenges so they survive cache flushes.
 */
class RequestPhoneOtp
{
    public function __construct(private readonly OtpChannel $channel) {}

    /**
     * @throws PhoneSignInBlocked
     * @throws ValidationException
     */
    public function handle(string $phone, ?string $ip, string $locale, OtpPurpose $purpose = OtpPurpose::Login): OtpChallenge
    {
        $this->ensurePhoneMaySignIn($phone);
        $this->ensureWithinLimits($phone, $ip);

        $length = (int) config('blueskills.otp.length');
        $code = str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);

        $challenge = OtpChallenge::query()->create([
            'phone' => $phone,
            'purpose' => $purpose,
            'channel' => $this->channel->name(),
            'code_hash' => OtpChallenge::hashCode($code),
            'expires_at' => now()->addMinutes((int) config('blueskills.otp.ttl_minutes')),
            'ip_address' => $ip,
        ]);

        SendOtpCode::dispatch($phone, $code, $locale);

        return $challenge;
    }

    /**
     * Deleted accounts and non-worker accounts cannot use phone sign-in (D-037).
     */
    public function ensurePhoneMaySignIn(string $phone): void
    {
        $user = User::withTrashed()->where('phone', $phone)->first();

        if ($user !== null && ($user->trashed() || ! $user->isWorker())) {
            throw new PhoneSignInBlocked;
        }
    }

    private function ensureWithinLimits(string $phone, ?string $ip): void
    {
        $latest = OtpChallenge::query()->where('phone', $phone)->latest('created_at')->first();
        $cooldown = (int) config('blueskills.otp.resend_seconds');

        if ($latest !== null && $latest->created_at->diffInSeconds(now()) < $cooldown) {
            throw ValidationException::withMessages([
                'phone' => __('auth.otp.wait', ['seconds' => (int) ceil($cooldown - $latest->created_at->diffInSeconds(now()))]),
            ]);
        }

        $since = now()->subHour();
        $perPhone = OtpChallenge::query()->where('phone', $phone)->where('created_at', '>=', $since)->count();
        $perIp = $ip === null ? 0 : OtpChallenge::query()->where('ip_address', $ip)->where('created_at', '>=', $since)->count();

        if ($perPhone >= (int) config('blueskills.otp.max_per_phone_per_hour')
            || $perIp >= (int) config('blueskills.otp.max_per_ip_per_hour')) {
            throw ValidationException::withMessages(['phone' => __('auth.otp.too_many')]);
        }
    }
}
