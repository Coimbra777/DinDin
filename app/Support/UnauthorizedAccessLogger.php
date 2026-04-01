<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class UnauthorizedAccessLogger
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function log(Request $request, string $reason, array $context = []): void
    {
        Log::channel('security')->warning('Unauthorized access attempt', array_merge([
            'reason' => $reason,
            'method' => $request->method(),
            'path' => $request->path(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
        ], $context));
    }
}
