<?php

namespace App\Domain\Crm\Support;

use App\Domain\Applications\Models\Application;
use App\Domain\Crm\Jobs\RefreshLeadStageJob;
use App\Domain\Mobility\Models\MobilityCase;
use App\Domain\Verification\Models\EvidenceItem;
use App\Domain\Worker\Models\SkillShow;
use App\Domain\Worker\Models\WorkerProfile;
use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Queues a lead stage refresh whenever something that moves the conversion stage changes:
 * profile completion, checked evidence, SkillShow moderation, applications and mobility cases.
 */
final class LeadStageWatcher
{
    public static function register(): void
    {
        WorkerProfile::saved(fn (WorkerProfile $profile) => self::when($profile, 'profile_completed_at', fn () => $profile->user_id));
        EvidenceItem::saved(fn (EvidenceItem $item) => self::when($item, 'status', fn () => self::profileUser($item->worker_profile_id)));
        SkillShow::saved(fn (SkillShow $show) => self::when($show, 'moderation_status', fn () => self::profileUser($show->worker_profile_id)));
        Application::saved(fn (Application $application) => self::when($application, 'status', fn () => LeadProgress::userIdFor($application)));
        MobilityCase::saved(fn (MobilityCase $case) => self::when($case, 'status', fn () => LeadProgress::userIdFor($case)));
    }

    /**
     * @param  Closure(): ?string  $userId
     */
    private static function when(Model $model, string $attribute, Closure $userId): void
    {
        if (! $model->wasRecentlyCreated && ! $model->wasChanged($attribute)) {
            return;
        }

        $id = $userId();

        if ($id !== null) {
            RefreshLeadStageJob::dispatch($id);
        }
    }

    private static function profileUser(string $profileId): ?string
    {
        return WorkerProfile::query()->whereKey($profileId)->value('user_id');
    }
}
