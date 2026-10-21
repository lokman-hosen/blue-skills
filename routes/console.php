<?php

use App\Domain\Messaging\Actions\SendNewApplicantDigests;
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
