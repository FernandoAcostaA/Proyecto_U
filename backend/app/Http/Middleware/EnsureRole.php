<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();
        $rolActual = $usuario?->rol?->nombre;

        if (! $rolActual || ! in_array($rolActual, $roles, true)) {
            abort(403, 'No tienes permisos para realizar esta accion.');
        }

        return $next($request);
    }
}
