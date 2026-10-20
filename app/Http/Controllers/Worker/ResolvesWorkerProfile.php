<?php

namespace App\Http\Controllers\Worker;

use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Http\Request;

trait ResolvesWorkerProfile
{
    /**
     * The signed-in worker's own Skill Passport. Every worker gets one at registration.
     */
    protected function workerProfile(Request $request): WorkerProfile
    {
        $profile = $request->user()?->workerProfile;
        abort_if($profile === null, 403);

        return $profile;
    }
}
