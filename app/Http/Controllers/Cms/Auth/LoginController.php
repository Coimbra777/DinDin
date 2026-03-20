<?php

namespace App\Http\Controllers\Cms\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

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
    protected $redirectTo = '/cms/finance/finance_dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
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
     * Campo do formulário continua a chamar-se "username" no POST (trait); aceita e-mail ou nome (`users.email` / `users.name`).
     */
    protected function attemptLogin(Request $request): bool
    {
        $login = trim((string) $request->input('username', ''));
        $password = (string) $request->input('password', '');

        $user = User::query()
            ->where(function ($q) use ($login) {
                $q->where('email', $login)
                    ->orWhere('name', $login);
            })
            ->first();

        if ($user === null || ! Hash::check($password, $user->getAuthPassword())) {
            return false;
        }

        $this->guard()->login($user, $request->boolean('remember'));

        return true;
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