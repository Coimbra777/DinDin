<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Models\BlogCategories;
use App\Models\BlogGallery;
use App\Models\BlogPosts;
use Illuminate\Support\Facades\DB;

class BlogCategoryService
{
    public function __construct(
        private readonly SlugService $slugService,
    ) {
    }

    public function store(array $data): BlogCategories
    {
        $data['slug'] = $this->slugService->getSlug($data['name'], 'blog_categories');

        return BlogCategories::query()->create($data);
    }

    public function update(BlogCategories $postCategory, array $data): void
    {
        $postActive = DB::table('blog_posts')
            ->join('blog_categories', 'blog_categories.id', '=', 'blog_posts.blog_category_id')
            ->where('blog_posts.blog_category_id', $postCategory->id)
            ->select('blog_posts.id', 'blog_posts.active')
            ->get();

        foreach ($postActive as $actives) {
            if ($actives->active != $data['active']) {
                $active = ['active' => '0'];
                $postStatus = BlogPosts::query()->find($actives->id);
                if ($postStatus) {
                    $postStatus->update($active);
                }
            }
        }

        $data['slug'] = $this->slugService->getSlug($data['name'], 'blog_categories', 'slug', $postCategory->id);

        $postCategory->update($data);
    }

    /**
     * @param  array<int|string>  $ids
     */
    public function deleteByIds(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $posts = BlogPosts::query()->whereIn('blog_category_id', $ids)->get();
        $categories = BlogCategories::query()->whereIn('id', $ids)->get();

        foreach ($categories as $post_category) {
            $post_category->delete();
        }

        foreach ($posts as $post) {
            $blog_gallery = BlogGallery::query()->where('blog_id', $post->id)->get();
            foreach ($blog_gallery as $gallery) {
                if (!empty($gallery->image)) {
                    @unlink($gallery->image);
                }
                $gallery->delete();
            }
            if (!empty($post->image)) {
                @unlink($post->image);
            }
            $post->delete();
        }
    }
}
