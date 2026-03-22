<?php

declare(strict_types=1);

use App\Http\Controllers\Finance\Api\FinanceGoalApiController;
use Illuminate\Support\Facades\Route;

/*
| Metas financeiras — incluído em routes/cms.php → /cms/finance/api/goals/*
*/
Route::get('/', [FinanceGoalApiController::class, 'index']);
Route::post('/', [FinanceGoalApiController::class, 'store']);
Route::get('/{finance_goal}', [FinanceGoalApiController::class, 'show']);
Route::put('/{finance_goal}', [FinanceGoalApiController::class, 'update']);
Route::delete('/{finance_goal}', [FinanceGoalApiController::class, 'destroy']);
Route::post('/{finance_goal}/sync', [FinanceGoalApiController::class, 'syncFromIncome']);
