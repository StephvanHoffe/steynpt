<?php

use App\Jobs\GeneratePlan;
use App\Services\Plans\Schedule as PlanSchedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Op de hosting draait elke minuut alleen de cronjob "php artisan schedule:run" (geen supervisor of Node).
// Die doet het volgende, in deze volgorde:

// 1. Ingeplande schema's waarvan de startdag is aangebroken publiceren (gebeurt ook bij het openen van een schemapagina).
Schedule::call(fn () => PlanSchedule::activateDuePlans())
    ->name('schemas-publiceren')
    ->everyMinute()
    ->withoutOverlapping(10);

// 2. De database-queue leegmaken: AI-concepten (job GeneratePlan). Een generatie kan minuten duren;
//    withoutOverlapping zorgt dat er maar één worker tegelijk draait (het slot verloopt na 15 minuten als
//    een worker hard is afgebroken). retry_after van de database-queue (DB_QUEUE_RETRY_AFTER, standaard 600)
//    is groter dan de time-out, zodat een lange generatie niet nog eens wordt gestart.
Schedule::command('queue:work', [
    '--stop-when-empty',
    '--tries=1',
    '--timeout='.GeneratePlan::TIMEOUT,
    '--sleep=1',
])
    ->everyMinute()
    ->withoutOverlapping(15);
