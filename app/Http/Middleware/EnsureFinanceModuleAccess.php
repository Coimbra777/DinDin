<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\GateNames;
use App\Support\UnauthorizedAccessLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garante acesso ao módulo financeiro (slug SaaS {@code finance}).
 * O acesso base a {@code finance} é concedido a qualquer utilizador autenticado; extras usam a pivot.
 * Regra canónica: {@see \Illuminate\Support\Facades\Gate} {@code finance.use} / {@code saas-module}.
 */
final class EnsureFinanceModuleAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null || ! Gate::forUser($user)->allows(GateNames::FINANCE)) {
            UnauthorizedAccessLogger::log($request, 'middleware.finance_module', ['gate' => GateNames::FINANCE]);
            if ($request->expectsJson() || $request->is('cms/finance/*')) {
                return response()->json(['message' => 'Módulo não autorizado'], 403);
            }
            abort(403, 'Módulo não autorizado');
        }

        return $next($request);
    }
}
