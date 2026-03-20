<?php

namespace App\Http\Controllers\Cms;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;

use App\Models\Group;

class RestrictedController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->user = Auth::user();
            if (!$this->checkModule($request)) {
                return redirect()->back()->with('error', 'Módulo não autorizado');
            }
            $data = [
                "request" => $request,
                "logos" => $this->setLogo(),
                "modules" => $this->makeMenu()
            ];

            View::share('data', $data);

            return $next($request);
        });
    }

    protected function headers($title, $moduleBreadcrumb = null)
    {
        $breadcrumb = [
            ["icon" => "fa fa-dashboard", "title" => "Dashboard", "url" => ""]
        ];
        if (!is_null($moduleBreadcrumb)) {
            $breadcrumb = [
                ["icon" => "fa fa-dashboard", "title" => "Dashboard", "url" => ""]
            ];
            $breadcrumb = array_merge($breadcrumb, $moduleBreadcrumb);
        }
        return json_encode([
            "title" => $title,
            "subtitle" => "",
            "breadcrumb" => $breadcrumb
        ]);
    }

    private function setLogo()
    {
        $headerLogo = env('CMS_HEADER_LOGO', 'img/logo_cms.svg');
        $logos = [
            'logo200' => asset($headerLogo),
            'logo50' => asset($headerLogo),
        ];
        return $logos;
    }

    private function makeMenu()
    {
        $userGroup = $this->user
            ->group()
            ->get();
        $groupMenu = $userGroup[0]
            ->menu();
        return $groupMenu;
    }

    private function checkModule($request)
    {
        $userGroup = $this->user
            ->group()
            ->get();
        $allowedModules = $userGroup[0]
            ->modules()
            ->get();

        $continue = false;
        foreach ($allowedModules as $key => $value) {
            if (empty($value->father_path)) {
                if ($request->is('cms/'.$value->path . "*")) {
                    $continue = true;
                }                
            }
        }

        //If the father module is not allowed
        if (!$continue) {
           return false;
        }

        foreach ($allowedModules as $key => $value) //Check submodule permission
        {
            if(!empty($value->father_path))
            {
                if($request->is('cms/' . $value->father_path . '/' . $value->path . "*")){
                    return true;
                }                
            }
        }

        return $continue;
    }
}
