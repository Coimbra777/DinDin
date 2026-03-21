<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Services\Cms\CmsShellDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

/**
 * Base para controladores CMS autenticados que partilham menu e logos nas views Blade.
 *
 * A autorização do módulo financeiro fica nos middlewares de rota (ex.: {@code finance.module}).
 */
class RestrictedController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            $this->user = Auth::user();

            if ($this->user === null) {
                abort(403);
            }

            $data = app(CmsShellDataService::class)->getData($this->user, $request);
            View::share('data', $data);

            return $next($request);
        });
    }

    protected function headers($title, $moduleBreadcrumb = null)
    {
        $breadcrumb = [
            ['icon' => 'fa fa-dashboard', 'title' => 'Dashboard', 'url' => ''],
        ];
        if (! is_null($moduleBreadcrumb)) {
            $breadcrumb = [
                ['icon' => 'fa fa-dashboard', 'title' => 'Dashboard', 'url' => ''],
            ];
            $breadcrumb = array_merge($breadcrumb, $moduleBreadcrumb);
        }

        return json_encode([
            'title' => $title,
            'subtitle' => '',
            'breadcrumb' => $breadcrumb,
        ]);
    }
}
