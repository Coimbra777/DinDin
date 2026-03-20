<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Category;
use Illuminate\Validation\Rule;

final class CategoryApiService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(int $userId): array
    {
        return Category::forUser($userId)
            ->orderBy('name')
            ->get()
            ->map(fn (Category $c) => $this->toArray($c))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $userId, array $data): Category
    {
        return Category::create([
            'user_id' => $userId,
            'name' => $data['name'],
            'type' => $data['type'] ?? Category::TYPE_EXPENSE,
            'group' => $data['group'] ?? null,
            'color' => $data['color'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category->fresh();
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Category $c): array
    {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'type' => $c->type,
            'group' => $c->group,
            'color' => $c->color,
            'slug' => $c->slug,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validationRules(): array
    {
        return [
            'name' => 'required|string|max:120',
            'color' => 'nullable|string|max:7',
            'type' => ['nullable', Rule::in([Category::TYPE_INCOME, Category::TYPE_EXPENSE])],
            'group' => ['nullable', 'string', 'max:32', Rule::in([
                Category::GROUP_FIXED,
                Category::GROUP_VARIABLE,
                Category::GROUP_FINANCIAL,
            ])],
        ];
    }
}
