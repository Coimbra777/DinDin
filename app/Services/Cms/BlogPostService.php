<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Models\BlogGallery;
use App\Models\BlogPosts;

class BlogPostService
{
    public function __construct(
        private readonly SlugService $slugService,
    ) {
    }

    public function store(array $data): BlogPosts
    {
        $data['slug'] = $this->slugService->getSlug($data['name'], 'blog_posts');

        return BlogPosts::query()->create($data);
    }

    public function update(BlogPosts $blog, array $data): void
    {
        $data['slug'] = $this->slugService->getSlug($data['name'], 'blog_posts', 'slug', $blog->id);

        $blog->update($data);
    }

    /**
     * @param  array<int|string>  $ids
     */
    public function deleteByIds(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $blogs = BlogPosts::query()->whereIn('id', $ids)->get();
        foreach ($blogs as $blog) {
            $blog_gallery = BlogGallery::query()->where('blog_id', $blog->id)->get();
            foreach ($blog_gallery as $gallery) {
                if (!empty($gallery->image)) {
                    @unlink($gallery->image);
                }
                $gallery->delete();
            }
            if (!empty($blog->image)) {
                @unlink($blog->image);
            }
            $blog->delete();
        }
    }
}
