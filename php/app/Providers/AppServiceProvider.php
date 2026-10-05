<?php

namespace App\Providers;

use App\Site\Texts;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Website-teksten: één keer per verzoek uit de database.
        $this->app->singleton(Texts::class);
    }

    public function boot(): void
    {
        // Tijden zijn onveranderlijk (geen per ongeluk aangepaste datum verderop in de code).
        Date::use(CarbonImmutable::class);

        // Tegen spam en scripts: per IP-adres, met een eigen teller per formulier.
        RateLimiter::for('registreren', fn (Request $request) => Limit::perMinute(10)->by('registreren|'.$request->ip()));
        RateLimiter::for('contact', fn (Request $request) => Limit::perMinute(10)->by('contact|'.$request->ip()));
    }
}
