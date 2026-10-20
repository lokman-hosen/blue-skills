<?php

namespace App\Domain\Worker\Data;

use App\Domain\Worker\Models\WorkerProfile;
use App\Domain\Worker\Support\ProfileCompletion;
use Spatie\LaravelData\Data;

class ProfileCompletionData extends Data
{
    /**
     * @param  list<ProfileStepData>  $steps
     */
    public function __construct(
        public int $percent,
        public array $steps,
        /** The first step not done yet; null when everything is done. */
        public ?string $next,
    ) {}

    public static function fromProfile(WorkerProfile $profile): self
    {
        $steps = app(ProfileCompletion::class)->steps($profile);
        $done = array_filter($steps);

        return new self(
            percent: (int) round(count($done) / count($steps) * 100),
            steps: array_map(fn (string $key, bool $isDone) => new ProfileStepData($key, $isDone), array_keys($steps), $steps),
            next: array_key_first(array_filter($steps, fn (bool $isDone) => ! $isDone)),
        );
    }
}
