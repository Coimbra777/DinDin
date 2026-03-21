<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class RestrictedController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, $next) {
            $this->user = Auth::user();

            if (! $this->checkModule($request)) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json(['message' => 'Módulo não autorizado'], 403);
                }

                return redirect()->back()->with('error', 'Módulo não autorizado');
            }

            $data = [
                'request' => $request,
                'logos' => $this->setLogo(),
                'modules' => $this->makeMenu(),
            ];

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

    private function setLogo()
    {
        $headerLogo = config('cms.header_logo', 'img/logo_cms.svg');
        $logos = [
            'logo200' => asset($headerLogo),
            'logo50' => asset($headerLogo),
        ];

        return $logos;
    }

    private function makeMenu()
    {
        $group = $this->authenticatedUserGroup();

        return $group ? $group->menu() : [];
    }

    private function authenticatedUserGroup(): ?Group
    {
        if ($this->user === null) {
            return null;
        }

        return $this->user->group;
    }

    /**
     * APIs JSON de finanças (prefixo /api/...) — requer módulo Finanças no grupo do utilizador.
     */
    private function isFinanceApiRequest(Request $request): bool
    {
        return $request->is(
            'cms/finance/api*',
            'api/finance*',
            'api/cards*',
            'api/projection*',
            'api/reports*',
            'api/goals*',
            'api/alerts*',
            'api/insights*',
            'api/credit-simulator*',
            'api/planning*',
            'api/dashboard',
            'api/credit-cards',
            'api/credit-cards/*',
        );
    }

    private function requiresFinanceEntitlement(Request $request): bool
    {
        return $request->is('cms/finance*') || $this->isFinanceApiRequest($request);
    }

    private function checkModule(Request $request): bool
    {
        if ($this->user === null) {
            return false;
        }

        if ($this->requiresFinanceEntitlement($request)) {
            return $this->user->canAccessSaasModule('finance');
        }

        $group = $this->authenticatedUserGroup();
        if ($group === null) {
            return false;
        }

        $allowedModules = $group->modules()->get();

        $continue = false;
        foreach ($allowedModules as $key => $value) {
            if (empty($value->father_path)) {
                if ($request->is('cms/'.$value->path.'*')) {
                    $continue = true;
                }
            }
        }

        if (! $continue) {
            return false;
        }

        foreach ($allowedModules as $key => $value) {
            if (! empty($value->father_path)) {
                if ($request->is('cms/'.$value->father_path.'/'.$value->path.'*')) {
                    return true;
                }
            }
        }

        return $continue;
    }
}
