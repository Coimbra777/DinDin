<?php

namespace App\Http\Controllers\Cms\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\CmsRegisterUserRequest;
use App\Models\Group;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegisterController extends Controller
{
    use RegistersUsers;

    protected $redirectTo = '/cms/finance/finance_dashboard';

    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showRegistrationForm(): View
    {
        return view('cms.auth.register');
    }

    public function register(CmsRegisterUserRequest $request)
    {
        $validated = $request->validated();
        $username = $this->uniqueUsernameFromEmail($validated['email']);

        event(new Registered($user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'whatsapp' => $validated['whatsapp'],
            'password' => bcrypt($validated['password']),
            'username' => $username,
            'group_id' => $this->selfRegisteredGroupId(),
        ])));

        $this->guard()->login($user);

        if ($response = $this->registered($request, $user)) {
            return $response;
        }

        return redirect($this->redirectPath());
    }

    private function selfRegisteredGroupId(): int
    {
        $configured = config('cms.self_registered_group_id');
        if ($configured !== null && $configured !== '' && (int) $configured > 0) {
            return (int) $configured;
        }

        $id = Group::query()->where('name', Group::NAME_SELF_REGISTERED)->value('id');

        return $id ? (int) $id : 0;
    }

    private function uniqueUsernameFromEmail(string $email): string
    {
        $local = Str::lower(Str::before($email, '@'));
        $local = preg_replace('/[^a-z0-9_]/', '_', $local) ?: 'user';
        $local = trim($local, '_') ?: 'user';
        $base = Str::limit($local, 200, '');
        $username = $base;
        $n = 0;

        while (User::query()->where('username', $username)->exists()) {
            $n++;
            $suffix = '_'.$n;
            $username = Str::limit($base, 255 - strlen($suffix), '').$suffix;
        }

        return $username;
    }
}
