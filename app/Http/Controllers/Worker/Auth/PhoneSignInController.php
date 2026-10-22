<?php

namespace App\Http\Controllers\Worker\Auth;

use App\Domain\Config\Data\CountryOptionData;
use App\Domain\Config\Models\Country;
use App\Domain\Crm\Support\LeadAttribution;
use App\Domain\Identity\Actions\FindWorkerForPhone;
use App\Domain\Identity\Actions\RegisterWorker;
use App\Domain\Identity\Actions\RequestPhoneOtp;
use App\Domain\Identity\Actions\VerifyPhoneOtp;
use App\Domain\Identity\Exceptions\PhoneSignInBlocked;
use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\CompleteWorkerRegistrationRequest;
use App\Http\Requests\Worker\RequestPhoneCodeRequest;
use App\Http\Requests\Worker\VerifyPhoneCodeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Worker sign-in and sign-up by phone: language → phone → code → name and country (spec §1).
 */
class PhoneSignInController extends Controller
{
    private const CHALLENGE = 'worker_auth.challenge_id';

    private const PHONE = 'worker_auth.phone';

    private const VERIFIED_PHONE = 'worker_auth.verified_phone';

    private const VERIFIED_AT = 'worker_auth.verified_at';

    /** Minutes a verified phone may be used to finish registration. */
    private const REGISTRATION_WINDOW = 30;

    public function language(): Response
    {
        return Inertia::render('auth/worker/language');
    }

    public function phone(): Response
    {
        return Inertia::render('auth/worker/phone', [
            'countries' => $this->sourceCountries(),
        ]);
    }

    public function sendCode(RequestPhoneCodeRequest $request, RequestPhoneOtp $requestOtp): RedirectResponse
    {
        $phone = $request->e164();

        try {
            $challenge = $requestOtp->handle($phone, $request->ip(), app()->getLocale());
        } catch (PhoneSignInBlocked) {
            throw ValidationException::withMessages(['phone' => __('auth.phone.blocked')]);
        }

        $request->session()->put([self::CHALLENGE => $challenge->id, self::PHONE => $phone]);

        return to_route('worker.verify');
    }

    public function verify(Request $request): Response|RedirectResponse
    {
        $phone = $request->session()->get(self::PHONE);

        if (! is_string($phone)) {
            return to_route('worker.phone');
        }

        return Inertia::render('auth/worker/verify', [
            'maskedPhone' => $this->mask($phone),
            'codeLength' => (int) config('blueskills.otp.length'),
            'resendSeconds' => (int) config('blueskills.otp.resend_seconds'),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function resend(Request $request, RequestPhoneOtp $requestOtp): RedirectResponse
    {
        $phone = $request->session()->get(self::PHONE);

        if (! is_string($phone)) {
            return to_route('worker.phone');
        }

        try {
            $challenge = $requestOtp->handle($phone, $request->ip(), app()->getLocale());
        } catch (PhoneSignInBlocked) {
            throw ValidationException::withMessages(['code' => __('auth.phone.blocked')]);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(['code' => $exception->errors()['phone'] ?? []]);
        }

        $request->session()->put(self::CHALLENGE, $challenge->id);

        return back()->with('status', __('auth.otp.resent'));
    }

    public function confirmCode(VerifyPhoneCodeRequest $request, VerifyPhoneOtp $verifyOtp, FindWorkerForPhone $findWorker): RedirectResponse
    {
        $challengeId = $request->session()->get(self::CHALLENGE);

        if (! is_string($challengeId)) {
            return to_route('worker.phone');
        }

        $phone = $verifyOtp->handle($challengeId, $request->string('code')->toString());

        try {
            $worker = $findWorker->handle($phone);
        } catch (PhoneSignInBlocked) {
            throw ValidationException::withMessages(['code' => __('auth.phone.blocked')]);
        }

        $request->session()->forget([self::CHALLENGE, self::PHONE]);

        if ($worker !== null) {
            Auth::login($worker, remember: true);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        $request->session()->put([self::VERIFIED_PHONE => $phone, self::VERIFIED_AT => now()->timestamp]);

        return to_route('worker.details');
    }

    public function details(Request $request): Response|RedirectResponse
    {
        if ($this->verifiedPhone($request) === null) {
            return to_route('worker.phone');
        }

        return Inertia::render('auth/worker/details', [
            'countries' => $this->sourceCountries(),
        ]);
    }

    public function register(CompleteWorkerRegistrationRequest $request, RegisterWorker $registerWorker, FindWorkerForPhone $findWorker): RedirectResponse
    {
        $phone = $this->verifiedPhone($request);

        if ($phone === null) {
            return to_route('worker.phone');
        }

        // Someone may have registered this number since the code was verified.
        try {
            $worker = $findWorker->handle($phone)
                ?? $registerWorker->handle(
                    $phone,
                    $request->string('name')->trim()->toString(),
                    $request->string('country')->toString(),
                    app()->getLocale(),
                    LeadAttribution::fromSession($request->session()),
                );
        } catch (PhoneSignInBlocked) {
            throw ValidationException::withMessages(['name' => __('auth.phone.blocked')]);
        }

        $request->session()->forget([self::VERIFIED_PHONE, self::VERIFIED_AT, LeadAttribution::SESSION_KEY]);
        Auth::login($worker, remember: true);
        $request->session()->regenerate();

        // Straight on to onboarding phase A; a worker who registered earlier just lands home.
        return $worker->workerProfile?->occupation_id === null
            ? to_route('worker.basics.edit')
            : to_route('dashboard');
    }

    private function verifiedPhone(Request $request): ?string
    {
        $phone = $request->session()->get(self::VERIFIED_PHONE);
        $verifiedAt = (int) $request->session()->get(self::VERIFIED_AT, 0);

        if (! is_string($phone) || now()->timestamp - $verifiedAt > self::REGISTRATION_WINDOW * 60) {
            return null;
        }

        return $phone;
    }

    /**
     * @return list<CountryOptionData>
     */
    private function sourceCountries(): array
    {
        return Country::query()
            ->whereIn('code', config('blueskills.worker_phone_countries'))
            ->orderBy('name')
            ->get()
            ->map(fn (Country $country) => CountryOptionData::fromCountry($country))
            ->values()
            ->all();
    }

    private function mask(string $phone): string
    {
        return substr($phone, 0, 4).str_repeat('•', max(strlen($phone) - 7, 0)).substr($phone, -3);
    }
}
