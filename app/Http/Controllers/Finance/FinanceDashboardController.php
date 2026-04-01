<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Cms\RestrictedController;
use App\Services\Finance\FinancialSummaryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Resumo mensal — foco em “quanto ainda posso gastar este mês”.
 */
class FinanceDashboardController extends RestrictedController
{
    public function __construct(
        private readonly FinancialSummaryService $summaries,
    ) {
        parent::__construct();
    }

    public function index(Request $request): View
    {
        $m = $request->query('month');
        $month = $this->summaries->normalizeMonth(is_string($m) ? $m : null);
        $initialView = 'dashboard';

        return view('cms.finance.spa', compact('initialView', 'month'));
    }
}
