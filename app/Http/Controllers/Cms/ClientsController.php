<?php

namespace App\Http\Controllers\Cms;

use App\Http\Requests\Cms\UpdateClientsRequest;
use App\Models\Clients;
use App\Services\Cms\ClientsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientsController extends RestrictedController
{
    public function __construct(
        private readonly ClientsService $clientsService,
    ) {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $data = $request->all();
        #PAGE TITLE E BREADCRUMBS
        $headers = parent::headers(
            "Clientes",
            [
                [
                    "icon" => "",
                    "title" => "Clientes",
                    "url" => "",
                ],
            ]
        );
        #LISTA DE ITENS
        $titles = json_encode(["#", "Status", "Nome", "E-mail"]);
        $actions = json_encode([
            [
                'path' => '{item}/edit',
                'icon' => 'fa fa-eye',
                'label' => 'Perfil',
                'color' => 'primary',
            ],
        ]);

        $busca = '';
        $pagination = 15;
        if (!empty($data['busca'])) {
            if ($data['busca'] != null && $data['busca'] != '') {
                $busca = $data['busca'];
            }
            $pagination = 500;
        }
        $items = Clients::select('id', 'active', 'name', 'email')
            ->where(function ($query) use ($data) {
                if (!empty($data['busca'])) {
                    $query->where('name', 'LIKE', "%" . $data['busca'] . "%");
                }
            })
            ->orWhere(function ($query) use ($data) {
                if (!empty($data['busca'])) {
                    $query->where('email', 'LIKE', "%" . $data['busca'] . "%");
                }
            })
            ->orWhere(function ($query) use ($data) {
                if (!empty($data['busca'])) {
                    $query->where('id', $data['busca']);
                }
            })
            ->orderBy('id', 'asc')
            ->paginate($pagination);

        foreach ($items as $item) {
            $item['active'] = [
                'type' => 'badge',
                'status' => $item['active'] == 1 ? 'success' : 'danger',
                'text' => $item['active'] == 1 ? 'Ativo' : 'Inativo'
            ];
        }

        return view('cms.clients.index', compact('headers', 'titles', 'items', 'busca', 'actions'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Clients $client): View
    {
        #PAGE TITLE E BREADCRUMBS
        $headers = parent::headers(
            "Clientes",
            [
                ["icon" => "", "title" => "Clientes", "url" => route('clients.index')],
                ["icon" => "", "title" => "Editar", "url" => ""],
            ]
        );

        $clients = $client;
        $inputs = [
            'E-mail' => $client->email,
            'Nome' => $client->name,
        ];

        return view('cms.clients.edit', compact('headers', 'clients', 'inputs'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientsRequest $request, Clients $client): RedirectResponse
    {
        $this->clientsService->update($client, $request->validated());

        return redirect()->route('clients.index')->with('message', 'Registro atualizado com sucesso!');
    }
}
