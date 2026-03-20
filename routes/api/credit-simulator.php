<?php

declare(strict_types=1);

use App\Http\Controllers\Finance\Api\CreditSimulatorApiController;
use Illuminate\Support\Facades\Route;

Route::post('/simulate', [CreditSimulatorApiController::class, 'simulate']);
