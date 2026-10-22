<?php

namespace App\Http\Middleware;

use App\Domain\Applications\Models\Application;
use App\Domain\Config\Data\LocaleData;
use App\Domain\Config\Support\LocaleRegistry;
use App\Domain\Config\Support\Translations;
use App\Domain\Identity\Data\AuthUserData;
use App\Domain\Identity\Models\User;
use App\Domain\Institute\Models\Course;
use App\Domain\Institute\Models\TalentPool;
use App\Domain\Jobs\Models\JobMandate;
use App\Domain\Jobs\Models\RecruitmentProject;
use App\Domain\Messaging\Support\Unread;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Organization\Data\OrganizationSummaryData;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Support\CurrentOrganization;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    public function __construct(
        private readonly LocaleRegistry $locales,
        private readonly Translations $translations,
        private readonly CurrentOrganization $currentOrganization,
    ) {}

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();
        $locale = app()->getLocale();
        $organization = $user !== null && $request->hasSession()
            ? $this->currentOrganization->resolve($user, $request->session())
            : null;

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user !== null ? AuthUserData::fromUser($user) : null,
            ],
            'currentOrganization' => $organization !== null ? OrganizationSummaryData::fromOrganization($organization) : null,
            // What the signed-in member may do in the current organization (hides links that would 403).
            'organizationAbilities' => [
                'update' => $organization !== null && $user->can('update', $organization),
                'manageTeam' => $organization !== null && $user->can('manageTeam', $organization),
                'reviewCompliance' => $organization !== null && $user->can('reviewCompliance', $organization),
                'viewCases' => $organization !== null && $user->can('viewAny', [MobilityCase::class, $organization]),
                'viewMandates' => $organization !== null && $user->can('viewAny', [JobMandate::class, $organization]),
                // Institutes (Module 7) and browsing their pools.
                'viewCourses' => $organization !== null && $user->can('viewAny', [Course::class, $organization]),
                'managePools' => $organization !== null && $user->can('viewAny', [TalentPool::class, $organization]),
                'viewPlacements' => $organization !== null && $user->can('viewPlacements', $organization),
                'browsePools' => $organization !== null && $user->can('browse', [TalentPool::class, $organization]),
                'viewApplicants' => $organization !== null && $user->can('viewAny', [Application::class, $organization]),
                // Projects, analytics and billing (Module 9).
                'viewProjects' => $organization !== null && $user->can('viewAny', [RecruitmentProject::class, $organization]),
                'viewAnalytics' => $organization !== null && $user->can('viewAnalytics', $organization),
                'viewBilling' => $organization !== null && $user->can('viewBilling', $organization),
            ],
            'organizations' => fn () => $user?->organizations
                ->map(fn (Organization $organization) => OrganizationSummaryData::fromOrganization($organization))
                ->values()
                ->all() ?? [],
            'locale' => new LocaleData(
                code: $locale,
                dir: $this->locales->isRtl($locale) ? 'rtl' : 'ltr',
                translationPending: $this->locales->isTranslationPending($locale),
            ),
            'locales' => fn () => $this->locales->available()->all(),
            'translations' => fn () => $this->translations->for($locale),
            // Navigation badges, refreshed live through Reverb (Module 5).
            'unread' => fn () => $user !== null ? [
                'messages' => Unread::conversations($user, $organization),
                'notifications' => $user->unreadNotifications()->count(),
            ] : null,
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
