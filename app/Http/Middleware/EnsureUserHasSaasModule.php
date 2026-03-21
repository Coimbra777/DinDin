<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasSaasModule
{
    public function handle(Request $request, Closure $next, string $slug): Response
    {
        $user = $request->user();
        if ($user === null || ! $user->canAccessSaasModule($slug)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Módulo não autorizado'], 403);
            }
            abort(403, 'Módulo não autorizado');
        }

        return $next($request);
    }
}
