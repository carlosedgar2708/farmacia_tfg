<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermisoMiddleware
{
    public function handle(Request $request, Closure $next, string $slug): Response
    {
        $user = $request->user();

        // si no hay usuario -> 403
        if (!$user) {
            abort(403, 'No autorizado');
        }

        // Admin siempre puede pasar
        if ($user->esAdmin()) {
            return $next($request);
        }

        // si tiene el permiso puntual -> pasa
        if ($user->tienePermiso($slug)) {
            return $next($request);
        }

        abort(403, 'No autorizado');
    }
}
