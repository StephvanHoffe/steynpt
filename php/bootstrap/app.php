<?php

use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureMember;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\UserLocale;
use App\Site\Locale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Taal: eerst op adres of cookie (ook voor een 404), daarna de taal van een ingelogd lid.
        $middleware->prepend(SetLocale::class);
        $middleware->web(append: [UserLocale::class]);
        // De taalkeuze is geen geheim en wordt al vóór de sessie gelezen.
        $middleware->encryptCookies(except: [Locale::COOKIE]);
        $middleware->alias([
            // Ingelogd lid met tweestapsverificatie en een geldig wachtwoord.
            'member' => EnsureMember::class,
            // Alleen Steyn (beheer).
            'admin' => EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
