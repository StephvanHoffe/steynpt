<?php

use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

// --- Website ---
Route::get('/', [SiteController::class, 'home']);
Route::get('/online-coaching', [SiteController::class, 'onlineCoaching']);
foreach (['personal-training', 'ademcoaching', 'voedingscoaching', 'tarieven', 'over-steyn', 'vriend-uitnodigen', 'privacy'] as $slug) {
    Route::get("/{$slug}", [SiteController::class, 'page'])->defaults('slug', $slug);
}
Route::get('/contact', [SiteController::class, 'contact']);
Route::post('/contact', [SiteController::class, 'storeContact'])->middleware('throttle:contact');
Route::get('/r/{code}', [SiteController::class, 'referral']);
Route::get('/robots.txt', [SiteController::class, 'robots']);
Route::get('/sitemap.xml', [SiteController::class, 'sitemap']);

// Oude WordPress-URL's blijven werken.
Route::permanentRedirect('/over-steynpt', '/over-steyn');
Route::permanentRedirect('/vraag-een-gratis-kennismaking-aan', '/contact');
Route::permanentRedirect('/kennismaking', '/contact');
Route::permanentRedirect('/rewards', '/vriend-uitnodigen');
Route::permanentRedirect('/small-group-training', '/personal-training');
Route::permanentRedirect('/feed', '/');

require __DIR__.'/account.php';
require __DIR__.'/admin.php';
require __DIR__.'/schemas.php';
