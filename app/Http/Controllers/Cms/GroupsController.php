<?php

namespace App\Http\Controllers\Cms;

use App\Http\Requests\Cms\StoreGroupRequest;
use App\Http\Requests\Cms\UpdateGroupRequest;
use App\Models\Group;
use App\Models\Module;
use App\Services\Cms\GroupsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupsController extends RestrictedController
{
    public function __construct(
        private readonly GroupsService $groupsService,
    ) {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $headers = parent::headers(
            "Grupos de Usuários",
            [["icon" => "", "title" => "Grupo de Usuários", "url" => ""]]
        );

        $actions = json_encode([
            [
                'path' => '{item}/edit',
                'icon' => 'fa fa-pencil',
                'label' => 'Editar',
                'color' => 'primary',
            ],
        ]);

        $titles = json_encode(['#', 'Nome']);

        $itemsPerPage = (int) config('constants.options.items_per_page', 15);

        if (!empty($request->busca)) {
            $busca = $request->busca;
            $items = Group::listItems($busca, $itemsPerPage);
        } else {
            $busca = '';
            $items = Group::listItems(null, $itemsPerPage);
        }

        $modules = Module::getModules();

        return view('cms.groups.index', compact('headers', 'titles', 'items', 'modules', 'busca', 'actions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $this->groupsService->store($request->validated());

        return redirect()->back()->with('message', 'Registro gravado com sucesso!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Group $group): View|RedirectResponse
    {
        $headers = parent::headers(
            'Grupos de Usuários',
            [
                ['icon' => '', 'title' => 'Grupos de Usuários', 'url' => route('groups.index')],
                ['icon' => '', 'title' => 'Editar', 'url' => ''],
            ]
        );

        $item = $group;

        $modules = Module::getModules();

        $group_modules = $item->modules()->get();

        $group_modules_ids = [];
        foreach ($group_modules as $value) {
            $group_modules_ids[] = $value->id;
        }

        return view('cms.groups.edit', compact('headers', 'item', 'modules', 'group_modules_ids'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGroupRequest $request, Group $group): RedirectResponse
    {
        $this->groupsService->update($group, $request->validated());

        return redirect()->route('groups.index')->with('message', 'Registro atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $data = $request->all();
        $this->groupsService->deleteMany($data['registro'] ?? []);

        return redirect()->back()->with('message', 'Itens excluídos com sucesso!');
    }
}
