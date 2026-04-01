<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\GateNames;
use App\Support\UnauthorizedAccessLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null || ! Gate::forUser($user)->allows(GateNames::ADMIN)) {
            UnauthorizedAccessLogger::log($request, 'middleware.admin', ['gate' => GateNames::ADMIN]);
            if ($request->expectsJson()) {
                abort(403, 'Acesso reservado a administradores.');
            }
            abort(403, 'Acesso reservado a administradores.');
        }

        return $next($request);
    }
}
