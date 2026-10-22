<?php

namespace App\Domain\Crm\Support;

use App\Domain\Applications\Models\Application;
use App\Domain\Crm\Enums\ConversionStage;
use App\Domain\Identity\Models\User;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Mobility\States\MobilityCase\Completed;
use App\Domain\Verification\States\Evidence\IssuerVerified;
use App\Domain\Worker\Enums\ModerationStatus;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Database\Eloquent\Builder;

/**
 * The conversion stages read from what a worker actually did (D-095). The same definitions
 * feed the lead stage and the campaign funnel, so the two always agree.
 *
 * - profile complete: the Skill Passport was completed;
 * - qualified: profile complete and at least one checked item (issuer-verified evidence or an
 *   approved SkillShow);
 * - applied: at least one application;
 * - selected: an application reached Selected, Offer, Accepted or Declined (after an offer);
 * - deployed: a mobility case closed as joined.
 */
final class LeadProgress
{
    /** Application statuses that mean the worker was selected. */
    public const SELECTED_STATUSES = ['selected', 'offer', 'accepted', 'declined'];

    public static function stageFor(User $user): ConversionStage
    {
        $profile = $user->workerProfile;

        if ($profile === null) {
            return ConversionStage::Registered;
        }

        $id = $profile->id;

        return match (true) {
            self::deployed(WorkerProfile::query()->whereKey($id))->exists() => ConversionStage::Deployed,
            self::selected(WorkerProfile::query()->whereKey($id))->exists() => ConversionStage::Selected,
            self::applied(WorkerProfile::query()->whereKey($id))->exists() => ConversionStage::Applied,
            self::qualified(WorkerProfile::query()->whereKey($id))->exists() => ConversionStage::Qualified,
            $profile->profile_completed_at !== null => ConversionStage::ProfileComplete,
            default => ConversionStage::Registered,
        };
    }

    /**
     * Which stages the worker has actually reached, each checked on its own: someone can apply
     * without being qualified, so stages aren't assumed from the furthest one.
     *
     * @return array<string, bool> keyed by ConversionStage value, without Prospect
     */
    public static function reached(?User $user): array
    {
        $profile = $user?->workerProfile;
        $check = fn (callable $scope) => $profile !== null && $scope(WorkerProfile::query()->whereKey($profile->id))->exists();

        return [
            ConversionStage::Registered->value => $user !== null,
            ConversionStage::ProfileComplete->value => $profile?->profile_completed_at !== null,
            ConversionStage::Qualified->value => $check(self::qualified(...)),
            ConversionStage::Applied->value => $check(self::applied(...)),
            ConversionStage::Selected->value => $check(self::selected(...)),
            ConversionStage::Deployed->value => $check(self::deployed(...)),
        ];
    }

    /**
     * @param  Builder<WorkerProfile>  $profiles
     * @return Builder<WorkerProfile>
     */
    public static function profileComplete(Builder $profiles): Builder
    {
        return $profiles->whereNotNull('worker_profiles.profile_completed_at');
    }

    /**
     * @param  Builder<WorkerProfile>  $profiles
     * @return Builder<WorkerProfile>
     */
    public static function qualified(Builder $profiles): Builder
    {
        return self::profileComplete($profiles)->where(fn (Builder $query) => $query
            ->whereHas('evidenceItems', fn (Builder $evidence) => $evidence->where('status', IssuerVerified::$name))
            ->orWhereHas('skillShows', fn (Builder $show) => $show->where('moderation_status', ModerationStatus::Approved->value)));
    }

    /**
     * @param  Builder<WorkerProfile>  $profiles
     * @return Builder<WorkerProfile>
     */
    public static function applied(Builder $profiles): Builder
    {
        return $profiles->whereHas('applications');
    }

    /**
     * @param  Builder<WorkerProfile>  $profiles
     * @return Builder<WorkerProfile>
     */
    public static function selected(Builder $profiles): Builder
    {
        return $profiles->whereHas('applications', fn (Builder $query) => $query->whereIn('applications.status', self::SELECTED_STATUSES));
    }

    /**
     * @param  Builder<WorkerProfile>  $profiles
     * @return Builder<WorkerProfile>
     */
    public static function deployed(Builder $profiles): Builder
    {
        return $profiles->whereHas('applications', fn (Builder $query) => $query
            ->whereIn('applications.id', MobilityCase::query()->select('application_id')->where('status', Completed::$name)));
    }

    /**
     * The worker a record belongs to, for refreshing their lead.
     */
    public static function userIdFor(WorkerProfile|Application|MobilityCase $record): ?string
    {
        return match (true) {
            $record instanceof WorkerProfile => $record->user_id,
            $record instanceof Application => WorkerProfile::query()->whereKey($record->worker_profile_id)->value('user_id'),
            default => WorkerProfile::query()
                ->whereKey(Application::query()->whereKey($record->application_id)->value('worker_profile_id'))
                ->value('user_id'),
        };
    }
}
