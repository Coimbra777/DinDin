<?php

declare(strict_types=1);

namespace App\Services\Finance;

use Illuminate\Support\Facades\Cache;

/**
 * Revisão por utilizador para invalidar leituras cacheadas (dashboard, relatórios)
 * sem enumerar chaves nem depender de tags de cache.
 */
final class FinanceReadCache
{
    private const REVISION_PREFIX = 'finance.cache_rev.';

    public function revision(int $userId): int
    {
        return (int) Cache::get($this->key($userId), 0);
    }

    public function bump(int $userId): void
    {
        Cache::forever($this->key($userId), $this->revision($userId) + 1);
    }

    private function key(int $userId): string
    {
        return self::REVISION_PREFIX.$userId;
    }
}
