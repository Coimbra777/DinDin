<?php

declare(strict_types=1);

namespace App\Http\Controllers\Cms\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\Admin\AdminUpdateUserRequest;
use App\Models\SaasModule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserAdminController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->get('q', ''));

        $q = User::query()->orderBy('name');
        if ($search !== '') {
            $q->where(function ($sub) use ($search) {
                $sub->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        return view('cms.admin.users.index', [
            'users' => $q->paginate(20)->withQueryString(),
            'q' => $search,
        ]);
    }

    public function edit(User $user): View
    {
        $modules = SaasModule::query()->orderBy('name')->get();

        return view('cms.admin.users.edit', [
            'user' => $user->load('saasModules'),
            'modules' => $modules,
        ]);
    }

    public function update(AdminUpdateUserRequest $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id && $user->isAdmin() && ! $request->boolean('is_admin')) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['is_admin' => 'Não pode remover a sua própria flag de administrador.']);
        }

        $user->update([
            'name' => $request->validated('name'),
            'is_admin' => $request->boolean('is_admin'),
        ]);
        $user->saasModules()->sync($request->input('saas_module_ids', []));

        return redirect()
            ->route('cms.admin.users.index')
            ->with('success', 'Utilizador atualizado.');
    }
}
