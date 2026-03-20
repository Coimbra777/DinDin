<?php

namespace App\Http\Middleware;

use Closure;

/**
 * Placeholder middleware: module access for CMS and finance APIs is enforced in
 * {@see \App\Http\Controllers\Cms\RestrictedController} (constructor closure).
 * Register this only if you extract that logic into a dedicated middleware.
 */
class GroupModuleChecker
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        return $next($request);
    }
}
