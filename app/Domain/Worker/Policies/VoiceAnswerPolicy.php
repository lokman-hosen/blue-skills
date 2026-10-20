<?php

namespace App\Domain\Worker\Policies;

use App\Domain\Identity\Models\User;
use App\Domain\Worker\Models\VoiceAnswer;

/**
 * Voice answers and their recordings belong to the worker alone.
 */
class VoiceAnswerPolicy
{
    public function view(User $user, VoiceAnswer $answer): bool
    {
        return $this->owns($user, $answer);
    }

    public function update(User $user, VoiceAnswer $answer): bool
    {
        return $this->owns($user, $answer);
    }

    private function owns(User $user, VoiceAnswer $answer): bool
    {
        return $user->isWorker() && $answer->workerProfile()->where('user_id', $user->id)->exists();
    }
}
