<?php

namespace App\Http\Controllers\Cms;

use App\Http\Requests\Cms\UpdatePageRequest;
use App\Models\Page;
use App\Services\Cms\CmsImageUploadService;
use App\Services\Cms\PageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class PageController extends RestrictedController
{
    public function __construct(
        private readonly PageService $pageService,
        private readonly CmsImageUploadService $imageUploadService,
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
            "Páginas",
            [
                [
                    "icon" => "",
                    "title" => "Páginas",
                    "url" => ""
                ]
            ]
        );
        #LISTA DE ITENS
        $titles = json_encode(["#", 'Status', 'Título', "Local"]);
        $actions = json_encode([
            [
                'path' => '{item}/edit',
                'icon' => 'fa fa-pencil',
                'label' => 'Editar',
                'color' => 'primary'
            ]
        ]);

        $busca = '';
        $pagination = 15;
        if (!empty($data['busca'])) {
            if ($data['busca'] != null && $data['busca'] != '') {
                $busca = $data['busca'];
            }
            $pagination = 500;
        }
        $items = Page::select('id', 'active', 'name', 'location')
            ->paginate($pagination);

        foreach ($items as $item) {
            $item['active'] = [
                'type' => 'badge',
                'status' => $item['active'] == 1 ? 'success' : 'danger',
                'text' => $item['active'] == 1 ? 'Ativo' : 'Inativo'
            ];
        }

        return view('cms.pages.index', compact('headers', 'titles', 'items', 'actions', 'busca'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Page $page): View|RedirectResponse
    {
        #PAGE TITLE E BREADCRUMBS
        $headers = parent::headers(
            "Páginas",
            [
                ["icon" => "", "title" => "Páginas", "url" => route('pages.index')],
                ["icon" => "", "title" => "Editar", "url" => ""],
            ]
        );

        if (empty($page)) {
            return redirect()->back();
        }

        $address = ['cep' => $page->CEP, 'state' => $page->state, 'city' => $page->city, 'street' => $page->street, 'number' => $page->number];
        $address = json_encode($address);

        return view('cms.pages.edit', compact('headers', 'page', 'address'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePageRequest $request, Page $page): RedirectResponse
    {
        $data = $request->validated();

        $image = !empty($page->image) ? $page->image : '';

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $uploadedPath = $this->imageUploadService->upload('pages', $file, 800);
            if ($uploadedPath === false) {
                return redirect()->back()->withErrors(['errors' => 'image cannot be uploaded'])->withInput();
            }

            if ($page->image && File::exists($page->image)) {
                unlink($page->image);
            }
            $image = $uploadedPath;
        }

        $data['image'] = $image !== '' ? $image : null;

        $this->pageService->update($page, $data);

        return redirect()->route('pages.index')->with('message', 'Registro atualizado com sucesso!');
    }
}
