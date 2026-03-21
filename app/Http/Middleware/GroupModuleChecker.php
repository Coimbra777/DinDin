<?php

namespace App\Http\Middleware;

use Closure;

/**
 * Placeholder — acesso ao módulo financeiro: {@see EnsureFinanceModuleAccess} ({@code finance.module} nas rotas).
 */
class GroupModuleChecker
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        return $next($request);
    }
}
