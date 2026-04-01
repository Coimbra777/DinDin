<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasSaasModule
{
    public function handle(Request $request, Closure $next, string $slug): Response
    {
        $user = $request->user();
        if ($user === null || ! Gate::forUser($user)->allows('saas-module', $slug)) {
            if ($request->expectsJson() || $request->is('cms/finance/*')) {
                return response()->json(['message' => 'Módulo não autorizado'], 403);
            }
            abort(403, 'Módulo não autorizado');
        }

        return $next($request);
    }
}
