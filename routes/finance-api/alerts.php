<?php

declare(strict_types=1);

use App\Http\Controllers\Finance\Api\FinanceAlertApiController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FinanceAlertApiController::class, 'index']);
