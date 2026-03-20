<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance\Api;

use App\Http\Controllers\Cms\RestrictedController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceOnboardingApiController extends RestrictedController
{
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'onboarding_completed' => (bool) $request->user()->onboarding_completed,
        ]);
    }

    public function complete(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->onboarding_completed) {
            $user->update(['onboarding_completed' => true]);
        }

        return response()->json([
            'onboarding_completed' => true,
        ]);
    }
}
