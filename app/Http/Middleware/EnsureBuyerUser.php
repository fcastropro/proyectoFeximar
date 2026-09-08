<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBuyerUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isBuyerUser()) {
            abort(403, 'Solo usuarios de comprador pueden acceder al portal de clientes.');
        }

        $buyer = $user->primaryBuyer();

        if (! $buyer) {
            abort(403, 'No hay un comprador activo asociado a este usuario.');
        }

        $request->attributes->set('currentBuyer', $buyer);

        return $next($request);
    }
}
