<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showLinkRequestForm(): View
    {
        return view('cms.layouts.auth-vue', ['authPage' => 'forgot']);
    }

    /**
     * Sempre a mesma mensagem (não revelar se o e-mail existe).
     */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        return redirect()
            ->route('cms.password.forgot')
            ->with('status', 'Se o e-mail estiver cadastrado, você receberá um link para redefinir a senha.');
    }
}
