<?php

namespace App\Http\Controllers\Worker;

use App\Domain\Applications\Models\Consent;
use App\Domain\Crm\Actions\SetStaffContactPreference;
use App\Domain\Identity\Actions\RequestPhoneOtp;
use App\Domain\Identity\Actions\VerifyPhoneOtp;
use App\Domain\Identity\Enums\OtpPurpose;
use App\Domain\Identity\Exceptions\PhoneSignInBlocked;
use App\Domain\Institute\Data\WorkerCohortData;
use App\Domain\Institute\Models\CohortMember;
use App\Domain\Worker\Actions\DeleteWorkerAccount;
use App\Domain\Worker\Actions\UpdateProfileVisibility;
use App\Domain\Worker\Data\ConsentRecordData;
use App\Domain\Worker\Enums\ProfileVisibility;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Who can see the Skill Passport, consent history, BLUESKILLS follow-up calls (D-095), and
 * deleting the account (spec §3, D-044).
 */
class PrivacyController extends Controller
{
    use ResolvesWorkerProfile;

    private const DELETION_CHALLENGE = 'worker_privacy.deletion_challenge_id';

    public function show(Request $request): Response
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('view', $profile);

        $phone = (string) $request->user()?->phone;

        return Inertia::render('worker/privacy', [
            'visibility' => $profile->visibility,
            'consents' => $profile->consents()->latest('granted_at')->limit(100)->get()
                ->map(fn (Consent $consent) => ConsentRecordData::fromConsent($consent))
                ->values()
                ->all(),
            // Institutes the worker joined (product rule 11, D-082).
            'institutes' => $profile->cohortMemberships()->accepted()->with(['cohort.organization', 'cohort.course'])->latest('responded_at')->get()
                ->map(fn (CohortMember $member) => WorkerCohortData::fromMember($member))->values()->all(),
            'staffContactAllowed' => $profile->staff_contact_opted_out_at === null,
            'deletionCodeSent' => $request->session()->has(self::DELETION_CHALLENGE),
            'maskedPhone' => substr($phone, 0, 4).str_repeat('•', max(strlen($phone) - 7, 0)).substr($phone, -3),
            'codeLength' => (int) config('blueskills.otp.length'),
        ]);
    }

    public function updateVisibility(Request $request, UpdateProfileVisibility $update): RedirectResponse
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);

        $validated = $request->validate(['visibility' => ['required', Rule::enum(ProfileVisibility::class)]]);
        $update->handle($profile, ProfileVisibility::from($validated['visibility']));
        Inertia::flash('toast', ['type' => 'success', 'message' => __('privacy.visibility_saved')]);

        return to_route('worker.privacy.show');
    }

    public function updateStaffContact(Request $request, SetStaffContactPreference $setPreference): RedirectResponse
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);

        $validated = $request->validate(['allowed' => ['required', 'boolean']]);
        $setPreference->handle($profile, (bool) $validated['allowed']);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('privacy.staff_contact.saved')]);

        return to_route('worker.privacy.show');
    }

    public function sendDeletionCode(Request $request, RequestPhoneOtp $requestOtp): RedirectResponse
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);

        try {
            $challenge = $requestOtp->handle((string) $request->user()?->phone, $request->ip(), app()->getLocale(), OtpPurpose::AccountDeletion);
        } catch (PhoneSignInBlocked) {
            abort(403);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(['code' => $exception->errors()['phone'] ?? []]);
        }

        $request->session()->put(self::DELETION_CHALLENGE, $challenge->id);

        return to_route('worker.privacy.show');
    }

    public function destroy(Request $request, VerifyPhoneOtp $verifyOtp, DeleteWorkerAccount $delete): RedirectResponse
    {
        $profile = $this->workerProfile($request);
        Gate::authorize('update', $profile);

        $request->validate(['code' => ['required', 'string', 'size:'.config('blueskills.otp.length')]]);
        $challengeId = $request->session()->get(self::DELETION_CHALLENGE);

        if (! is_string($challengeId)) {
            throw ValidationException::withMessages(['code' => __('privacy.delete.send_first')]);
        }

        $phone = $verifyOtp->handle($challengeId, $request->string('code')->toString(), OtpPurpose::AccountDeletion);
        $user = $request->user();
        abort_unless($user !== null && $phone === (string) $user->phone, 403);

        $delete->handle($user);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
