<?php

use App\Http\Controllers\LocaleController;
use App\Http\Controllers\SiteController;
use App\Site\Locale;
use Illuminate\Support\Facades\Route;

// --- Website ---
// Elke openbare pagina heeft ook een Engels adres (/en/…, zie App\Site\Locale::PATHS); de taal volgt uit het adres.
foreach (['nl', 'en'] as $taal) {
    $at = fn (string $path) => Locale::path($path, $taal);
    Route::get($at('/'), [SiteController::class, 'home']);
    Route::get($at('/online-coaching'), [SiteController::class, 'onlineCoaching']);
    foreach (['personal-training', 'ademcoaching', 'voedingscoaching', 'tarieven', 'over-steyn', 'vriend-uitnodigen', 'privacy'] as $slug) {
        Route::get($at("/{$slug}"), [SiteController::class, 'page'])->defaults('slug', $slug);
    }
    Route::get($at('/contact'), [SiteController::class, 'contact']);
    Route::post($at('/contact'), [SiteController::class, 'storeContact'])->middleware('throttle:contact');
}
// Taalknop op pagina's zonder Engels adres (inloggen, Mijn omgeving): onthoudt de keuze en gaat terug.
Route::get('/taal/{taal}', [LocaleController::class, 'switch'])->whereIn('taal', Locale::SUPPORTED);
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
Route::permanentRedirect('/10-weken-programma', '/tarieven');
Route::permanentRedirect('/our-team', '/over-steyn');
Route::permanentRedirect('/my-account', '/account');

// Demopagina's van het oude WordPress-thema (shop, producten, events, portfolio, blog, demo-homepages) en
// WordPress-bestanden: 410 "bestaat niet meer", zodat zoekmachines ze snel uit de resultaten halen.
Route::any('{oud}', fn () => abort(410))->where('oud', '(?:'.implode('|', [
    'shop(?:-2|-page|-home)?', 'cart(?:-2)?', 'checkout(?:-2)?', 'my-account-2', 'product', 'product-category', 'product-tag',
    'events', 'events-category', 'portfolio', 'blog', 'testimonials-category', 'author',
    'home-1', 'parallax-home', 'martial-arts-home', 'fitness-home', 'fullscreen-home', 'landing',
    'sample-page(?:-2)?', 'about-the-class', 'class-timetable', 'timetable', 'bmi-calculator',
    'wp-content', 'wp-includes', 'wp-json', 'wp-admin', 'wp-login\\.php', 'xmlrpc\\.php', 'wp-sitemap[^/]*\\.xml', 'sitemap_index\\.xml',
]).')(?:/.*)?');

require __DIR__.'/account.php';
require __DIR__.'/admin.php';
require __DIR__.'/schemas.php';
