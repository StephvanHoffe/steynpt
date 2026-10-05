<?php

use App\Http\Controllers\Admin\AgendaController;
use App\Http\Controllers\Admin\AgendaSettingsController;
use App\Http\Controllers\Admin\IcalController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\RequestController;
use App\Http\Controllers\Admin\SiteTextController;
use Illuminate\Support\Facades\Route;

// Beheer (alleen voor Steyn): overzicht, leden, aanvragen, agenda, instellingen en website-teksten.
// De schema-onderdelen staan in routes/schemas.php.
Route::middleware(['member', 'admin'])->prefix('admin')->group(function () {
    Route::get('/', [OverviewController::class, 'index']);
    Route::post('/vriendenkorting', [OverviewController::class, 'markReferralReward']);

    // --- Leden ---
    Route::get('/leden', [MemberController::class, 'index']);
    Route::get('/leden/{id}', [MemberController::class, 'show']);
    Route::post('/leden/{id}/coaching', [MemberController::class, 'updateCoaching']);
    Route::post('/leden/{id}/metingen', [MemberController::class, 'addMeasurement']);
    Route::post('/leden/{id}/metingen/verwijderen', [MemberController::class, 'deleteMeasurement']);
    Route::post('/leden/{id}/tweestaps-resetten', [MemberController::class, 'resetTwoFactor']);

    // --- Aanvragen via de contactpagina ---
    Route::get('/aanvragen', [RequestController::class, 'index']);
    Route::post('/aanvragen/afhandelen', [RequestController::class, 'toggle']);

    // --- Agenda ---
    Route::get('/agenda', [AgendaController::class, 'index']);
    Route::get('/agenda/nieuw', [AgendaController::class, 'create']);
    Route::post('/agenda/nieuw', [AgendaController::class, 'store']);
    Route::post('/agenda/annuleren', [AgendaController::class, 'cancel']);

    // --- Instellingen: beschikbaarheid, vrije dagen en de iCal-link ---
    Route::get('/agenda/instellingen', [AgendaSettingsController::class, 'show']);
    Route::post('/agenda/instellingen/beschikbaarheid', [AgendaSettingsController::class, 'addAvailability']);
    Route::post('/agenda/instellingen/beschikbaarheid/verwijderen', [AgendaSettingsController::class, 'deleteAvailability']);
    Route::post('/agenda/instellingen/blokkades', [AgendaSettingsController::class, 'addBlock']);
    Route::post('/agenda/instellingen/blokkades/verwijderen', [AgendaSettingsController::class, 'deleteBlock']);
    Route::post('/agenda/instellingen/ical-link', [AgendaSettingsController::class, 'rotateIcalToken']);

    // --- Website-teksten ---
    Route::get('/teksten', [SiteTextController::class, 'index']);
    Route::get('/teksten/{pagina}', [SiteTextController::class, 'edit']);
    Route::post('/teksten/{pagina}', [SiteTextController::class, 'update']);
});

// iCal-abonnement voor Steyn: /ical/<token>.ics (Google Agenda, Apple Agenda, Outlook). Het geheime token is de toegang.
Route::get('/ical/{file}', [IcalController::class, 'feed'])->middleware('throttle:60,1');
