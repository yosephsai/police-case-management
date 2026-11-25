<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectToHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('app.force_https') && ! $request->secure()) {
            return redirect()->away(
                str_replace('http://', 'https://', $request->fullUrl()),
                301
            );
        }

        return $next($request);
    }
}
