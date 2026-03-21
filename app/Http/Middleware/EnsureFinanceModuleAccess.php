<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\ModuleAccessContract;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garante acesso ao módulo financeiro (slug SaaS {@code finance}).
 * O acesso base a {@code finance} é concedido a qualquer utilizador autenticado; extras usam a pivot.
 * Ver {@see ModuleAccessContract}.
 */
final class EnsureFinanceModuleAccess
{
    public function __construct(
        private readonly ModuleAccessContract $moduleAccess,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null || ! $this->moduleAccess->can($user, 'finance')) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Módulo não autorizado'], 403);
            }
            abort(403, 'Módulo não autorizado');
        }

        return $next($request);
    }
}
