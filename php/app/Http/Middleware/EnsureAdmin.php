<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Alleen voor Steyn (role = admin). Gebruik samen met 'member', die het inloggen al controleert. */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            return redirect('/account');
        }

        return $next($request);
    }
}
