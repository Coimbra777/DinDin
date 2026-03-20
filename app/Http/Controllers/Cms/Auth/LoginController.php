<?php

namespace App\Http\Controllers\Cms\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
     */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/cms/configurations';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        if (config('finance.redirect_cms_dashboard_to_finance')) {
            $this->redirectTo = '/cms/finance/finance_dashboard';
        }
    }

    public function showLoginForm()
    {
        return view('cms.auth.login');

    }

    public function username()
    {
        return 'username';

    }

    /**
     * Após logout: ir para o login do CMS (evita redirect('/') do trait).
     * Caminho relativo: no Docker (ex.: localhost:8004) mantém host e porta.
     */
    protected function loggedOut(Request $request): RedirectResponse
    {
        return redirect()->route('login');
    }
}