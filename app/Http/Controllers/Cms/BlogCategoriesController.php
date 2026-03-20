<?php

namespace App\Http\Controllers\Cms;

use App\Http\Requests\Cms\StoreBlogCategoryRequest;
use App\Http\Requests\Cms\UpdateBlogCategoryRequest;
use App\Models\BlogCategories;
use App\Services\Cms\BlogCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogCategoriesController extends RestrictedController
{
    public function __construct(
        private readonly BlogCategoryService $blogCategoryService,
    ) {
        parent::__construct();
    }

    public function index(Request $request): View
    {
        $data = $request->all();
        $headers = parent::headers(
            'Categorias do blog',
            [
                [
                    'icon' => '',
                    'title' => 'Categorias do blog',
                    'url' => '',
                ],
            ]
        );

        $titles = json_encode(['#', 'Status', 'Nome']);
        $actions = json_encode([
            [
                'path' => '{item}/edit',
                'icon' => 'fa fa-pencil',
                'label' => 'editar',
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
        $items = BlogCategories::select('id', 'active', 'name')
            ->where(function ($query) use ($data) {
                if (!empty($data['busca'])) {
                    $query->where('name', 'LIKE', '%' . $data['busca'] . '%');
                }
            })
            ->orWhere(function ($query) use ($data) {
                if (!empty($data['busca'])) {
                    $query->where('active', 'LIKE', '%' . $data['busca'] . '%');
                }
            })
            ->paginate($pagination);

        foreach ($items as $item) {
            $item['active'] = [
                'type' => 'badge',
                'status' => $item['active'] == 1 ? 'success' : 'danger',
                'text' => $item['active'] == 1 ? 'Ativo' : 'Inativo',
            ];
        }

        return view('cms.blog.categories.index', compact('headers', 'titles', 'items', 'actions', 'busca'));
    }

    public function store(StoreBlogCategoryRequest $request): RedirectResponse
    {
        $this->blogCategoryService->store($request->validated());

        return redirect()->back()->with('message', 'Registro cadastrado com sucesso!');
    }

    public function edit(BlogCategories $blog_category): View|RedirectResponse
    {
        $post_category = $blog_category;
        $headers = parent::headers(
            'Categorias do blog',
            [
                ['icon' => '', 'title' => 'Categoria do blog', 'url' => route('blog_categories.index')],
                ['icon' => '', 'title' => 'Editar', 'url' => ''],
            ]
        );

        if (empty($post_category)) {
            return redirect()->back();
        }

        return view('cms.blog.categories.edit', compact('headers', 'post_category'));
    }

    public function update(UpdateBlogCategoryRequest $request, BlogCategories $blog_category): RedirectResponse
    {
        $this->blogCategoryService->update($blog_category, $request->validated());

        return redirect()->route('blog_categories.index')->with('message', 'Registro atualizado com sucesso!');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->blogCategoryService->deleteByIds($request->input('registro', []));

        return redirect()->back()->with('message', 'Itens excluídos com sucesso!');
    }
}
