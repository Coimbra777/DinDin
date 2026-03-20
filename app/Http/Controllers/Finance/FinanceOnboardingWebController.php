<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Cms\RestrictedController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Marca onboarding como concluído na BD (POST web + redirect).
 * Evita depender de XHR/axios para persistir o estado.
 */
class FinanceOnboardingWebController extends RestrictedController
{
    public function complete(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user->onboarding_completed) {
            $user->update(['onboarding_completed' => true]);
        }

        $query = [];
        $month = $request->input('month') ?? $request->query('month');
        if (is_string($month) && $month !== '') {
            $query['month'] = $month;
        }

        return redirect()->route('finance_dashboard.index', $query);
    }
}
