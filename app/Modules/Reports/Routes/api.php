<?php

declare(strict_types=1);

use App\Modules\Reports\Http\Controllers\Api\ReportApiController;
use Illuminate\Support\Facades\Route;

/*
| Relatórios — prefixo pai "reports" → /api/reports/*
*/
Route::get('/categories', [ReportApiController::class, 'categories']);
Route::get('/trend', [ReportApiController::class, 'trend']);
