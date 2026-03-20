<?php

declare(strict_types=1);

use App\Http\Controllers\Finance\Api\FinancePlanningApiController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FinancePlanningApiController::class, 'index']);
Route::post('/', [FinancePlanningApiController::class, 'store']);
Route::get('/{finance_monthly_plan}', [FinancePlanningApiController::class, 'show']);
Route::put('/{finance_monthly_plan}', [FinancePlanningApiController::class, 'update']);
Route::delete('/{finance_monthly_plan}', [FinancePlanningApiController::class, 'destroy']);
