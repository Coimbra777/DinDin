<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->getMethod() === 'OPTIONS') {
            return $this->addHeaders($request, response('', 204));
        }

        /** @var Response $response */
        $response = $next($request);

        return $this->addHeaders($request, $response);
    }

    private function addHeaders(Request $request, Response $response): Response
    {
        $origin = $this->resolveAllowedOrigin($request);

        return $response
            ->header('Access-Control-Allow-Origin', $origin)
            ->header('Access-Control-Allow-Headers', 'Content-Type, X-Auth-Token, Authorization, Origin, X-CSRF-TOKEN, X-Requested-With')
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Credentials', $origin !== '*' ? 'true' : 'false');
    }

    private function resolveAllowedOrigin(Request $request): string
    {
        $configured = config('cors.allowed_origins', []);
        if ($configured === []) {
            $appUrl = rtrim((string) config('app.url', ''), '/');
            $configured = $appUrl !== '' ? [$appUrl] : [];
        }

        if ($configured === []) {
            return '*';
        }

        if (in_array('*', $configured, true)) {
            return '*';
        }

        $requestOrigin = $request->headers->get('Origin');
        if ($requestOrigin && in_array($requestOrigin, $configured, true)) {
            return $requestOrigin;
        }

        return $configured[0];
    }
}
