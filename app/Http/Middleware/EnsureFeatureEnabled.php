<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea un grupo de rutas si su módulo está apagado en config/features.php
 * (ver .env, FEATURE_*). Se usa como `->middleware('feature:tickets')`.
 */
class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        abort_unless((bool) config("features.{$feature}", false), 404);

        return $next($request);
    }
}
