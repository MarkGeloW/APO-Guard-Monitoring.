<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class UseRequestRootUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = strtolower($request->getHost());

        $isTunnel = str_contains($host, 'trycloudflare.com')
            || str_contains($host, 'cfargotunnel.com')
            || $request->headers->has('CF-Connecting-IP');

        if ($isTunnel) {
            URL::forceRootUrl($request->getSchemeAndHttpHost());
            URL::forceScheme('https');
        }

        return $next($request);
    }
}
