<?php

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
