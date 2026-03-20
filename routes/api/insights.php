<?php

declare(strict_types=1);

use App\Http\Controllers\Finance\Api\FinanceInsightApiController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FinanceInsightApiController::class, 'index']);
