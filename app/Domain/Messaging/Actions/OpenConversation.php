<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Applications\Models\Application;
use App\Domain\Messaging\Models\Conversation;

/**
 * The application's conversation, created the first time either side opens it.
 */
class OpenConversation
{
    public function handle(Application $application): Conversation
    {
        return Conversation::query()->firstOrCreate(
            ['application_id' => $application->id],
            ['organization_id' => $application->job->organization_id, 'worker_profile_id' => $application->worker_profile_id],
        );
    }
}
