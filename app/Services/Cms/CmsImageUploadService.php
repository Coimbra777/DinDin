<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Traits\UploadTrait;

/**
 * Ponto único de upload/remoção de imagens do CMS (substitui uso direto de UploadTrait nos controllers).
 */
class CmsImageUploadService
{
    use UploadTrait;

    public function upload(string $directory, $file, int|bool $resizeWidthOrBanner = false): string|false
    {
        return static::uploadValidFile($directory, $file, $resizeWidthOrBanner);
    }

    public function remove(?string $storedPath): bool
    {
        if ($storedPath === null || $storedPath === '') {
            return true;
        }

        return (bool) static::deleteFile($storedPath);
    }
}
