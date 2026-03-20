<?php

declare(strict_types=1);

namespace App\Services\Cms;

use App\Models\Page;

class PageService
{
    /** IDs que no legado tinham `active` forçado para 1 no update. */
    private const FIXED_ACTIVE_PAGE_IDS = [1, 2, 3, 4, 5, 6, 7];

    public function update(Page $page, array $data): void
    {
        if (!empty($data['video']) && is_string($data['video'])) {
            $data['video'] = str_replace('watch?v=', 'embed/', $data['video']);
        }

        if (in_array((int) $page->id, self::FIXED_ACTIVE_PAGE_IDS, true)) {
            $data['active'] = 1;
        }

        $page->update($data);
    }
}
