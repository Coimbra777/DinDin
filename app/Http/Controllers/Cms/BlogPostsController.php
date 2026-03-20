<?php

namespace App\Http\Controllers\Cms;

use App\Http\Requests\Cms\StoreBlogPostRequest;
use App\Http\Requests\Cms\UpdateBlogPostRequest;
use App\Models\BlogCategories;
use App\Models\BlogGallery;
use App\Models\BlogPosts;
use App\Services\Cms\BlogPostService;
use App\Services\Cms\CmsImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogPostsController extends RestrictedController
{
    public function __construct(
        private readonly BlogPostService $blogPostService,
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
        $headers = parent::headers(
            'Postagens',
            [
                [
                    'icon' => '',
                    'title' => 'Postagens',
                    'url' => '',
                ],
            ]
        );
        $titles = json_encode(['#', 'Status', 'Destaque', 'Nome', 'Categoria', 'Data agendada']);
        $actions = json_encode([
            [
                'path' => '{item}/edit',
                'icon' => 'fa fa-pencil',
                'label' => 'editar',
                'color' => 'primary',
            ],
            [
                'path' => '{item}/gallery',
                'icon' => 'fa fa-image',
                'label' => 'Imagens',
                'color' => 'primary',
            ],
        ]);
        $busca = '';
        $pagination = 15;
        if (!empty($data['busca'])) {
            if ($data['busca'] != null && $data['busca'] != '') {
                $busca = $data['busca'];
            }
        }
        $items = BlogPosts::select('id', 'active', 'highlight', 'name', 'blog_category_id', 'date')
            ->where(function ($query) use ($data) {
                if (!empty($data['busca'])) {
                    $query->where('name', 'LIKE', '%' . $data['busca'] . '%');
                }
            })
            ->orWhere(function ($query) use ($data) {
                if (!empty($data['busca'])) {
                    $query->where('date', 'LIKE', '%' . $data['busca'] . '%');
                }
            })
            ->orWhere(function ($query) use ($data) {
                if (!empty($data['busca'])) {
                    $query->where('active', 'LIKE', '%' . $data['busca'] . '%');
                }
            })
            ->orderBy('id', 'DESC')->paginate($pagination);

        foreach ($items as $item) {
            $item['active'] = [
                'type' => 'badge',
                'status' => $item['active'] == 1 ? 'success' : 'danger',
                'text' => $item['active'] == 1 ? 'Ativo' : 'Inativo',
            ];
            $item['highlight'] = [
                'type' => 'badge',
                'status' => $item['highlight'] == 1 ? 'success' : 'danger',
                'text' => $item['highlight'] == 1 ? 'Destaque' : 'Não destaque',
            ];
        }

        foreach ($items as $item) {
            $item->date = date('d/m/Y', strtotime($item->date));
            $guarda = BlogCategories::select('name')->where('id', $item->blog_category_id)->first();
            if (isset($guarda->name)) {
                $item->blog_category_id = $guarda->name;
            } else {
                $item->blog_category_id = '';
            }
        }

        $categoryList = [];
        $categories = BlogCategories::where('active', '1')->get();
        foreach ($categories as $key => $category) {
            $categoryList[$key]['value'] = $category->id;
            $categoryList[$key]['label'] = $category->name;
        }

        foreach ($items as $key => $value) {
            if (empty($items[$key]->date)) {
                $items[$key]->date = 'Não possui';
            }
        }

        return view('cms.blog.posts.index', compact('headers', 'titles', 'items', 'actions', 'categoryList', 'busca'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBlogPostRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['image']);

        $file = $request->file('image');
        $path = $this->imageUploadService->upload('blogs', $file, 800);
        if ($path === false) {
            return redirect()->back()->withErrors(['errors' => 'image cannot be uploaded'])->withInput();
        }
        $data['image'] = $path;

        $this->blogPostService->store($data);

        return redirect()->back()->with('message', 'Registro cadastrado com sucesso!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BlogPosts $blog_post): View|RedirectResponse
    {
        $blog = $blog_post;
        $headers = parent::headers(
            'Postagens',
            [
                ['icon' => '', 'title' => 'Postagens', 'url' => route('blog_posts.index')],
                ['icon' => '', 'title' => 'Editar', 'url' => ''],
            ]
        );

        if (empty($blog)) {
            return redirect()->back();
        }

        $categoryList = [];
        $categories = BlogCategories::get();
        foreach ($categories as $key => $category) {
            $categoryList[$key]['value'] = $category->id;
            $categoryList[$key]['label'] = $category->name;
        }

        return view('cms.blog.posts.edit', compact('headers', 'blog', 'categoryList'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBlogPostRequest $request, BlogPosts $blog_post): RedirectResponse
    {
        $blog = $blog_post;
        $data = $request->validated();
        unset($data['image']);

        $image = $blog->image;
        if ($request->hasFile('image')) {
            $uploaded = $this->imageUploadService->upload('blog_posts', $request->file('image'), 800);
            if ($uploaded === false) {
                return redirect()->back()->withErrors(['errors' => 'image cannot be uploaded'])->withInput();
            }
            if ($blog->image) {
                @unlink($blog->image);
            }
            $image = $uploaded;
        }

        $data['image'] = $image;

        $this->blogPostService->update($blog, $data);

        return redirect()->route('blog_posts.index')->with('message', 'Registro atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $this->blogPostService->deleteByIds($request->input('registro', []));

        return redirect()->back()->with('message', 'Itens excluídos com sucesso!');
    }

    public function preview(string $slug): View
    {
        $blog_post = BlogPosts::where('slug', $slug)->firstOrFail();
        $blog_gallery = BlogGallery::where('blog_id', $blog_post->id)->get();

        $blog_post->image = asset($blog_post->image);

        $blog_post->blog_category_id = BlogCategories::select('name')->where('id', $blog_post->blog_category_id)->first()['name'];

        $blog_post->date = strftime('%d de %B, %Y', strtotime($blog_post->date));

        $blog_post->time = date('H\hm', strtotime($blog_post->time));

        foreach ($blog_gallery as $photo) {
            $photo->image = asset($photo->image);
        }

        $today = date('Y-m-d');
        $news = BlogPosts::where('date', '<=', $today)->orderBy('date', 'DESC')->limit(6)->get();
        $news_categories = BlogCategories::select('name', 'id')->get();
        foreach ($news as $new) {
            $new->date = strftime('%d de %B, %Y', strtotime($new->date));
            foreach ($news_categories as $category) {
                if ($new->blog_category_id == $category->id) {
                    $new->category = $category->name;
                }
            }
        }

        return view('website.blog.internal', compact('blog_post', 'blog_gallery', 'news'));
    }
}
