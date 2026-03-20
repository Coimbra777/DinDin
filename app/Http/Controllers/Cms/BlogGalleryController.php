<?php

namespace App\Http\Controllers\Cms;

use App\Http\Requests\Cms\StoreBlogGalleryRequest;
use App\Models\BlogGallery;
use App\Models\BlogPosts;
use App\Services\Cms\BlogGalleryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogGalleryController extends RestrictedController
{
    public function __construct(
        private readonly BlogGalleryService $blogGalleryService,
    ) {
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(BlogPosts $blog_post): View
    {
        $blog = $blog_post;
        $headers = parent::headers(
            'Imagens de Blog',
            [
                [
                    'icon' => '',
                    'title' => 'Postagens',
                    'url' => route('blog_posts.index'),
                ],
                [
                    'icon' => '',
                    'title' => 'Imagens do Blog',
                    'url' => '',
                ],
            ]
        );
        $titles = json_encode(['#', 'Imagem']);
        $actions = json_encode([]);
        $items = BlogGallery::where('blog_id', $blog->id)->paginate();
        foreach ($items as $key => $value) {
            unset($value->blog_id);
            $items[$key]->image = [
                'type' => 'img',
                'src' => asset($value->image),
            ];
        }

        return view('cms.blog.gallery.index', compact('headers', 'titles', 'items', 'actions', 'blog'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBlogGalleryRequest $request, BlogPosts $blog_post): RedirectResponse
    {
        $files = $request->file('attachments', []);
        if (!is_array($files)) {
            $files = [$files];
        }

        if (!$this->blogGalleryService->storeMany($blog_post->id, $files)) {
            return redirect()->back()->withErrors(['errors' => 'image cannot be uploaded'])->withInput();
        }

        return redirect()->back()->with('message', 'Registro cadastrado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BlogPosts $blog_post, Request $request): RedirectResponse
    {
        $this->blogGalleryService->deleteByIds($request->input('registro', []));

        return redirect()->back()->with('message', 'Itens excluídos com sucesso!');
    }
}
