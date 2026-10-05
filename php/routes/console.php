<?php

use App\Jobs\GeneratePlan;
use App\Models\ContactRequest;
use App\Services\Plans\Schedule as PlanSchedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Op de hosting draait elke minuut alleen de cronjob "php artisan schedule:run" (geen supervisor of Node).
// Alles draait binnen dat ene PHP-proces (Schedule::call in plaats van Schedule::command), zodat het ook werkt
// als de hosting proc_open/exec heeft uitgezet. Volgorde:

// 1. Ingeplande schema's waarvan de startdag is aangebroken publiceren (gebeurt ook bij het openen van een schemapagina).
Schedule::call(fn () => PlanSchedule::activateDuePlans())
    ->name('schemas-publiceren')
    ->everyMinute()
    ->withoutOverlapping(10);

// 2. Elke nacht een back-up van de database (storage/backups, 30 dagen bewaard).
Schedule::call(fn () => Artisan::call('steynpt:backup'))
    ->name('back-up')
    ->dailyAt('03:15')
    ->timezone('Europe/Amsterdam');

// 3. Elke nacht contactaanvragen ouder dan 12 maanden verwijderen (zie de privacyverklaring).
Schedule::call(fn () => Artisan::call('model:prune', ['--model' => [ContactRequest::class]]))
    ->name('aanvragen-opschonen')
    ->dailyAt('03:30')
    ->timezone('Europe/Amsterdam');

// 4. De database-queue leegmaken: AI-concepten (job GeneratePlan). Een generatie kan minuten duren;
//    withoutOverlapping zorgt dat er maar één worker tegelijk draait (het slot verloopt na 15 minuten als
//    een worker hard is afgebroken). retry_after van de database-queue (DB_QUEUE_RETRY_AFTER, standaard 600)
//    is groter dan de time-out, zodat een lange generatie niet nog eens wordt gestart.
Schedule::call(fn () => Artisan::call('queue:work', [
    '--stop-when-empty' => true,
    '--tries' => 1,
    '--timeout' => GeneratePlan::TIMEOUT,
    '--sleep' => 1,
]))
    ->name('wachtrij')
    ->everyMinute()
    ->withoutOverlapping(15);
