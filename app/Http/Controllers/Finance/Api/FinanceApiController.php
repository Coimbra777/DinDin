<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance\Api;

use App\Http\Controllers\Controller;

/**
 * Base para JSON API de finanças (sem shell Blade / menu CMS).
 *
 * {@see \App\Http\Controllers\Cms\RestrictedController} continua a ser usado
 * apenas por controladores que renderizam views Blade.
 */
abstract class FinanceApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
}
