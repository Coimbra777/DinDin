<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Regista uso de rotas espelhadas sob /api/* (routes/api.php) em vez do prefixo canónico
 * /cms/finance/api/* usado pela SPA. Facilita migração e remoção futura das duplicatas.
 */
final class LogDeprecatedFinanceApiMirror
{
    public function handle(Request $request, Closure $next): Response
    {
        Log::warning('Deprecated finance API mirror: use /cms/finance/api instead', [
            'path' => $request->path(),
            'method' => $request->method(),
            'route' => $request->route()?->getName(),
        ]);

        return $next($request);
    }
}
