<?php

use App\Http\Controllers\Account\PlanController as AccountPlanController;
use App\Http\Controllers\Admin\PlansController;
use Illuminate\Support\Facades\Route;

// Trainings- en voedingsschema's: beheer (/admin/trainingsschemas, /admin/voedingsschemas) en de weergave voor de klant (/account/schema/{id}).

Route::middleware(['member', 'admin'])->prefix('admin')->group(function () {
    // Oude adressen: schema's staan nu per type in een eigen onderdeel.
    Route::get('/schemas', fn () => redirect('/admin/trainingsschemas'));
    Route::get('/schemas/{id}', [PlansController::class, 'legacy'])->whereNumber('id');

    foreach (['trainingsschemas' => 'training', 'voedingsschemas' => 'voeding'] as $slug => $type) {
        Route::get("/{$slug}", [PlansController::class, 'index'])->defaults('type', $type);
        Route::get("/{$slug}/nieuw", [PlansController::class, 'create'])->defaults('type', $type);
        Route::post("/{$slug}/nieuw", [PlansController::class, 'store'])->defaults('type', $type);
        Route::get("/{$slug}/{id}", [PlansController::class, 'show'])->defaults('type', $type)->whereNumber('id');
        Route::post("/{$slug}/{id}", [PlansController::class, 'update'])->defaults('type', $type)->whereNumber('id');
        Route::post("/{$slug}/{id}/terugzetten", [PlansController::class, 'unschedule'])->defaults('type', $type)->whereNumber('id');
        Route::post("/{$slug}/{id}/voorbeeld", [PlansController::class, 'preview'])->defaults('type', $type)->whereNumber('id');
    }
});

Route::middleware('member')->get('/account/schema/{id}', [AccountPlanController::class, 'show'])->whereNumber('id');
