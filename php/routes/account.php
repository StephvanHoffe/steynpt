<?php

use App\Http\Controllers\Account\AgendaController;
use App\Http\Controllers\Account\DashboardController;
use App\Http\Controllers\Account\IntakeController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TwoFactorController;
use App\Models\Measurement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Inloggen, registreren en Mijn omgeving.

// --- Inloggen (wachtwoord, daarna een code uit de authenticator-app) ---
Route::get('/inloggen', [LoginController::class, 'show']);
Route::post('/inloggen', [LoginController::class, 'login']);
Route::post('/uitloggen', [LoginController::class, 'logout']);
Route::get('/registreren', [RegisterController::class, 'show']);
Route::post('/registreren', [RegisterController::class, 'register'])->middleware('throttle:registreren');

Route::get('/inloggen/verificatie', [TwoFactorController::class, 'show']);
// Pogingen worden per lid en per inlogpoging begrensd in App\Auth\Accounts (codes) en LoginController (wachtwoord).
Route::post('/inloggen/verificatie', [TwoFactorController::class, 'verify']);
Route::post('/inloggen/verificatie/instellen', [TwoFactorController::class, 'confirm']);
Route::post('/inloggen/verificatie/klaar', [TwoFactorController::class, 'finish']);

// Geen 'member'-middleware: die stuurt bij een verlopen wachtwoord juist hierheen.
Route::get('/wachtwoord-vernieuwen', [PasswordController::class, 'show']);
Route::post('/wachtwoord-vernieuwen', [PasswordController::class, 'update']);

// --- Mijn omgeving ---
// Losse afspraak als .ics (zonder sessie: 401 in plaats van een doorverwijzing).
Route::get('/account/agenda/{id}/ics', [AgendaController::class, 'ics']);

Route::middleware('member')->prefix('account')->group(function () {
    Route::get('/', [DashboardController::class, 'show']);
    Route::post('/check-in', [DashboardController::class, 'checkIn']);
    Route::post('/coaching', [DashboardController::class, 'requestCoaching']);
    Route::get('/agenda', [AgendaController::class, 'show']);
    Route::post('/agenda/boeken', [AgendaController::class, 'book']);
    Route::post('/agenda/afzeggen', [AgendaController::class, 'cancel']);
    Route::get('/intake', [IntakeController::class, 'show']);
    Route::post('/intake', [IntakeController::class, 'store']);
    Route::get('/voortgang', fn (Request $request) => view('account.progress', [
        'rows' => Measurement::query()->where('user_id', $request->user()->id)->orderBy('measured_at')->get(),
    ]));
    Route::get('/profiel', [ProfileController::class, 'show']);
    Route::post('/profiel', [ProfileController::class, 'update']);
    Route::post('/profiel/wachtwoord', [ProfileController::class, 'password']);
    Route::post('/profiel/herstelcodes', [ProfileController::class, 'regenerateCodes']);
    Route::post('/profiel/tweestaps-resetten', [ProfileController::class, 'resetTwoFactor']);
    Route::post('/profiel/verwijderen', [ProfileController::class, 'destroy']);
});
