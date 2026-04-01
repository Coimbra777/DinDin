<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\UnauthorizedAccessLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class UnauthorizedAccessLoggerTest extends TestCase
{
    public function test_logs_to_security_channel(): void
    {
        Log::shouldReceive('channel')->once()->with('security')->andReturnSelf();
        Log::shouldReceive('warning')->once()->withArgs(function (string $message, array $context): bool {
            return str_contains($message, 'Unauthorized')
                && ($context['reason'] ?? null) === 'test.denied';
        });

        UnauthorizedAccessLogger::log(Request::create('/cms/x', 'GET'), 'test.denied', ['gate' => 'x']);
    }
}
