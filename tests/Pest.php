<?php

use App\Domain\Identity\Models\User;
use App\Domain\Worker\Models\WorkerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/**
 * A worker straight after phone sign-up, with an empty Skill Passport (Module 2 tests).
 *
 * @param  array<string, mixed>  $profile
 */
function makeWorker(array $profile = []): User
{
    $user = User::factory()->worker()->create();
    WorkerProfile::factory()->fresh()->for($user)->create(['current_country_code' => 'BD', ...$profile]);

    return $user->load('workerProfile');
}
