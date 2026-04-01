<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null || ! Gate::forUser($user)->allows('admin.access')) {
            if ($request->expectsJson()) {
                abort(403, 'Acesso reservado a administradores.');
            }
            abort(403, 'Acesso reservado a administradores.');
        }

        return $next($request);
    }
}
