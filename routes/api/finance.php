<?php

declare(strict_types=1);

use App\Http\Controllers\Finance\Api\CategoryApiController;
use App\Http\Controllers\Finance\Api\DashboardApiController;
use App\Http\Controllers\Finance\Api\SummaryApiController;
use App\Http\Controllers\Finance\Api\TransactionApiController;
use Illuminate\Support\Facades\Route;

/*
| Finanças: transações, categorias, dashboard — incluído em routes/api.php com prefixo /api/finance
*/
Route::get('/dashboard', [DashboardApiController::class, 'show']);
Route::get('/dashboard/upcoming', [DashboardApiController::class, 'upcoming']);
Route::get('/summary', [SummaryApiController::class, 'show']);
Route::get('/transactions', [TransactionApiController::class, 'index']);
Route::get('/transactions/recent', [TransactionApiController::class, 'recent']);
Route::post('/transactions', [TransactionApiController::class, 'store']);
Route::post('/transactions/{transaction}/duplicate', [TransactionApiController::class, 'duplicate']);
Route::put('/transactions/{transaction}', [TransactionApiController::class, 'update']);
Route::delete('/transactions/{transaction}', [TransactionApiController::class, 'destroy']);
Route::get('/categories', [CategoryApiController::class, 'index']);
Route::post('/categories', [CategoryApiController::class, 'store']);
Route::put('/categories/{category}', [CategoryApiController::class, 'update']);
Route::delete('/categories/{category}', [CategoryApiController::class, 'destroy']);
