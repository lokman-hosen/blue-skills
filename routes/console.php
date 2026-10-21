<?php

use App\Domain\Jobs\Actions\PauseJobsUnderLapsedMandates;
use App\Domain\Messaging\Actions\ReleaseHeldMessages;
use App\Domain\Messaging\Actions\SendNewApplicantDigests;
use App\Domain\Verification\Actions\ExpireEvidence;
use App\Domain\Worker\Actions\PruneVoiceRecordings;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('voice:prune-recordings', function (PruneVoiceRecordings $prune) {
    $this->info("Deleted {$prune->handle()} voice recordings past retention.");
})->purpose('Delete Voice-to-CV recordings older than the retention period (D-047)');

Schedule::command('voice:prune-recordings')->dailyAt('03:15')->onOneServer();

Artisan::command('notifications:new-applicants', function (SendNewApplicantDigests $digests) {
    $this->info("Sent {$digests->handle(now()->subDay())} new-applicant digests.");
})->purpose('Tell organizations about yesterday\'s applicants, one alert per job (Module 5)');

// 06:00 UTC is mid-morning in the Gulf and early afternoon in South Asia.
Schedule::command('notifications:new-applicants')->dailyAt('06:00')->onOneServer();

Artisan::command('notifications:release-held', function (ReleaseHeldMessages $release) {
    $this->info("Released {$release->handle()} alerts held for quiet hours.");
})->purpose('Send WhatsApp and SMS alerts held during workers\' quiet hours (Module 5)');

Schedule::command('notifications:release-held')->everyFiveMinutes()->onOneServer()->withoutOverlapping();

Artisan::command('jobs:pause-lapsed-mandates', function (PauseJobsUnderLapsedMandates $pause) {
    $this->info("Paused {$pause->handle()} agency jobs whose mandate lapsed.");
})->purpose('Expire mandates past their end date and pause live agency jobs they no longer back (D-080)');

// Just after midnight in the Gulf, so a mandate ending yesterday stops backing jobs today.
Schedule::command('jobs:pause-lapsed-mandates')->dailyAt('20:30')->onOneServer();

Artisan::command('evidence:expire', function (ExpireEvidence $expire) {
    $this->info("Marked {$expire->handle()} evidence items expired.");
})->purpose('Mark verified worker evidence expired once its document passes its expiry date (D-090)');

Schedule::command('evidence:expire')->dailyAt('01:00')->onOneServer();
