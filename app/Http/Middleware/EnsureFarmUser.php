<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFarmUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isFarmUser()) {
            abort(403, 'Acceso restringido al portal de fincas.');
        }

        $farm = $user->primaryFarm();

        if (! $farm) {
            abort(403, 'No tienes una finca activa asignada.');
        }

        $request->attributes->set('currentFarm', $farm);

        return $next($request);
    }
}
