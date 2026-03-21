<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Category;
use Illuminate\Validation\ValidationException;

final class TransactionCategoryTypeGuard
{
    /**
     * @throws ValidationException
     */
    public static function assertCompatible(int $userId, ?int $categoryId, string $transactionType): void
    {
        if ($categoryId === null) {
            return;
        }

        $category = Category::forUser($userId)->whereKey($categoryId)->first();
        if ($category === null) {
            throw ValidationException::withMessages([
                'category_id' => 'A categoria selecionada não é válida.',
            ]);
        }

        $categoryType = ($category->type ?? '') !== '' ? $category->type : Category::TYPE_EXPENSE;
        if ($categoryType !== $transactionType) {
            throw ValidationException::withMessages([
                'category_id' => 'A categoria não corresponde ao tipo da transação (receita ou despesa).',
            ]);
        }
    }
}
