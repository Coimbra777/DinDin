<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Cms\RestrictedController;
use App\Models\Finance\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Resumo mensal — foco em “quanto ainda posso gastar este mês”.
 */
class FinanceDashboardController extends RestrictedController
{
    public function index(Request $request): View
    {
        $month = Transaction::normalizeMonth($request->query('month'));
        $initialView = 'dashboard';

        return view('cms.finance.spa', compact('initialView', 'month'));
    }
}
