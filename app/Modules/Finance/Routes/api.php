<?php

declare(strict_types=1);

use App\Modules\Finance\Http\Controllers\Api\CategoryApiController;
use App\Modules\Finance\Http\Controllers\Api\DashboardApiController;
use App\Modules\Finance\Http\Controllers\Api\SummaryApiController;
use App\Modules\Finance\Http\Controllers\Api\TransactionApiController;
use Illuminate\Support\Facades\Route;

/*
| Finanças: transações, categorias, dashboard — prefixo pai "finance" → /api/finance/*
*/
Route::get('/dashboard', [DashboardApiController::class, 'show']);
Route::get('/summary', [SummaryApiController::class, 'show']);
Route::get('/transactions', [TransactionApiController::class, 'index']);
Route::get('/transactions/recent', [TransactionApiController::class, 'recent']);
Route::post('/transactions', [TransactionApiController::class, 'store']);
Route::put('/transactions/{transaction}', [TransactionApiController::class, 'update']);
Route::delete('/transactions/{transaction}', [TransactionApiController::class, 'destroy']);
Route::get('/categories', [CategoryApiController::class, 'index']);
Route::post('/categories', [CategoryApiController::class, 'store']);
Route::put('/categories/{category}', [CategoryApiController::class, 'update']);
Route::delete('/categories/{category}', [CategoryApiController::class, 'destroy']);
