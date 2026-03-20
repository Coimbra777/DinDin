<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Models\BlogGallery;
use Illuminate\Http\UploadedFile;

class BlogGalleryService
{
    public function __construct(
        private readonly CmsImageUploadService $imageUploadService,
    ) {
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function storeMany(int $blogId, array $files): bool
    {
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid()) {
                return false;
            }
            $path = $this->imageUploadService->upload('blogs', $file, 800);
            if ($path === false) {
                return false;
            }
            BlogGallery::query()->create([
                'blog_id' => $blogId,
                'image' => $path,
            ]);
        }

        return true;
    }

    /**
     * @param  array<int|string>  $ids
     */
    public function deleteByIds(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $items = BlogGallery::query()->whereIn('id', $ids)->get();
        foreach ($items as $item) {
            if (!empty($item->image)) {
                @unlink($item->image);
            }
            $item->delete();
        }
    }
}
