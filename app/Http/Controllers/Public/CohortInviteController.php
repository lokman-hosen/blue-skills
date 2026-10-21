<?php

namespace App\Http\Controllers\Public;

use App\Domain\Institute\Actions\AcceptCohortInvitation;
use App\Domain\Institute\Actions\DeclineCohortInvitation;
use App\Domain\Institute\Data\CohortInviteData;
use App\Domain\Institute\Enums\CohortMemberStatus;
use App\Domain\Institute\Models\Cohort;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A cohort's invite link /i/{code} (prototype i-cohort, D-082). Anyone can open it; only a
 * signed-in worker can accept or decline, and nothing reaches the institute until they do
 * (product rule 11).
 */
class CohortInviteController extends Controller
{
    /** Remembered so a worker who signs up first finds the invitation on their home page. */
    public const SESSION_KEY = 'cohort_invite';

    public function show(Request $request, string $code): Response
    {
        $cohort = $this->cohort($code);
        $user = $request->user();
        $request->session()->put(self::SESSION_KEY, $cohort->invite_code);

        // Workers who sign in from here come straight back to answer.
        if ($user === null) {
            $request->session()->put('url.intended', route('cohort-invite.show', $cohort->invite_code));
        }

        $membership = $user?->isWorker() && $user->workerProfile !== null
            ? $cohort->members()->where('worker_profile_id', $user->workerProfile->id)->first()
            : null;

        return Inertia::render('public/cohort-invite', [
            'invite' => CohortInviteData::fromCohort($cohort),
            'viewer' => match (true) {
                $user === null => 'guest',
                ! $user->isWorker() => 'organization',
                $membership?->status === CohortMemberStatus::Accepted => 'member',
                default => 'worker',
            },
        ]);
    }

    public function accept(Request $request, string $code, AcceptCohortInvitation $accept): RedirectResponse
    {
        $cohort = $this->cohort($code);
        $profile = $request->user()?->workerProfile;
        abort_if($profile === null, 403);

        $accept->handle($cohort, $profile, $request->boolean('shares_progress'));
        $request->session()->forget(self::SESSION_KEY);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('cohorts.invite.accepted', ['institute' => (string) $cohort->organization->display_name])]);

        return to_route('worker.privacy.show');
    }

    public function decline(Request $request, string $code, DeclineCohortInvitation $decline): RedirectResponse
    {
        $cohort = $this->cohort($code);
        $profile = $request->user()?->workerProfile;
        abort_if($profile === null, 403);

        $decline->handle($cohort, $profile);
        $request->session()->forget(self::SESSION_KEY);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('cohorts.invite.declined')]);

        return to_route('dashboard');
    }

    private function cohort(string $code): Cohort
    {
        return Cohort::query()->where('invite_code', strtoupper($code))->firstOrFail();
    }
}
