<?php

namespace App\Http\Controllers\Cms;

use App\Http\Requests\Cms\StoreUserRequest;
use App\Http\Requests\Cms\UpdateUserRequest;
use App\Models\Group;
use App\Models\User;
use App\Services\Cms\UsersService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class UsersController extends RestrictedController
{
    public function __construct(
        private readonly UsersService $usersService,
    ) {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $headers = parent::headers(
            'Usuários',
            [['icon' => '', 'title' => 'Usuários', 'url' => '']]
        );

        $actions = json_encode([
            [
                'path' => '{item}/edit',
                'icon' => 'fa fa-pencil',
                'label' => 'Editar',
                'color' => 'primary',
            ],
        ]);

        $titles = json_encode(['#', 'Nome', 'E-mail', 'Usuário']);

        $busca = '';
        $items = User::select('id', 'name', 'email', 'username');
        if (!empty($request->busca)) {
            $busca = $request->busca;
            $items->where(function ($query) use ($busca) {
                $query->orWhere('id', 'like', '%' . $busca . '%')
                    ->orWhere('name', 'like', '%' . $busca . '%')
                    ->orWhere('email', 'like', '%' . $busca . '%')
                    ->orWhere('username', 'like', '%' . $busca . '%');
            });
        }
        $items = $items->orderBy('id', 'DESC')->orderBy('id', 'desc')->paginate();

        $groups = json_encode(Group::select('id AS value', 'name AS label')->get());

        return view('cms.users.index', compact('headers', 'titles', 'items', 'groups', 'busca', 'actions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        try {
            $this->usersService->store($request->validated(), $request->file('image'));
        } catch (RuntimeException $e) {
            return redirect()->back()->withErrors(['errors' => 'image cannot be uploaded'])->withInput();
        }

        return redirect()->back()->with('message', 'Registro cadastrado com sucesso!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {
        $headers = parent::headers(
            'Usuários',
            [
                ['icon' => '', 'title' => 'Usuários', 'url' => route('users.index')],
                ['icon' => '', 'title' => 'Editar', 'url' => ''],
            ]
        );

        $user->image = asset($user->image);

        $item = $user;

        $groups = json_encode(Group::select('id AS value', 'name AS label')->get());

        return view('cms.users.edit', compact('headers', 'item', 'groups'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $file = $request->hasFile('image') ? $request->file('image') : null;
        $this->usersService->update($user, $request->validated(), $file);

        return redirect()->route('users.index')->with('message', 'Registro atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $req): RedirectResponse
    {
        $this->usersService->deleteMany($req->input('registro', []));

        return redirect()->back()->with('message', 'Itens excluídos com sucesso!');
    }
}
