<?php

namespace App\Http\Controllers\Cms;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends RestrictedController
{
    /**
     * Painel inicial do CMS (neutro, extensível).
     */
    public function index(Request $request): View
    {
        $headers = parent::headers('Início', [
            ['icon' => '', 'title' => 'Início', 'url' => ''],
        ]);

        return view('cms.dashboard.index', compact('headers'));
    }
}
