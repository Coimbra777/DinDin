<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AdminSpaController extends Controller
{
    public function __invoke(): View
    {
        return view('cms.admin.spa');
    }
}
