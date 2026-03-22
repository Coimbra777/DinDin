<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Finance\Category;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class CategoryApiService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(int $userId): array
    {
        return Category::forUser($userId)
            ->withCount('transactions')
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
        $type = $data['type'] ?? Category::TYPE_EXPENSE;
        $group = $data['group'] ?? null;
        $this->validateTypeAndGroup($type, $group);

        if ($type === Category::TYPE_INCOME) {
            $group = null;
        }

        return Category::create([
            'user_id' => $userId,
            'name' => $data['name'],
            'type' => $type,
            'group' => $group,
            'color' => $data['color'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Category $category, array $data): Category
    {
        $originalType = $category->type ?? Category::TYPE_EXPENSE;
        if (array_key_exists('type', $data) && $data['type'] !== null && $data['type'] !== $originalType) {
            if ($category->transactions()->exists()) {
                throw ValidationException::withMessages([
                    'type' => 'Não é possível alterar o tipo de uma categoria com transações existentes.',
                ]);
            }
        }

        $typeAfter = $data['type'] ?? $originalType;
        $groupAfter = array_key_exists('group', $data) ? $data['group'] : $category->group;

        if ($typeAfter === Category::TYPE_INCOME) {
            $groupAfter = null;
            $data['group'] = null;
        }

        $this->validateTypeAndGroup($typeAfter, $groupAfter);

        $category->update($data);
        $category->refresh();
        $category->loadCount('transactions');

        return $category;
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
        $hasTransactions = array_key_exists('transactions_count', $c->getAttributes())
            ? (int) $c->getAttributes()['transactions_count'] > 0
            : $c->transactions()->exists();

        return [
            'id' => $c->id,
            'name' => $c->name,
            'type' => $c->type,
            'group' => $c->group,
            'color' => $c->color,
            'slug' => $c->slug,
            'has_transactions' => $hasTransactions,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validationRulesForStore(): array
    {
        return [
            'name' => 'required|string|max:120',
            'color' => 'nullable|string|max:7',
            'type' => ['required', Rule::in([Category::TYPE_INCOME, Category::TYPE_EXPENSE])],
            'group' => ['nullable', 'string', 'max:32', Rule::in([
                Category::GROUP_FIXED,
                Category::GROUP_VARIABLE,
                Category::GROUP_FINANCIAL,
            ])],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validationRulesForUpdate(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'color' => 'nullable|string|max:7',
            'type' => ['sometimes', Rule::in([Category::TYPE_INCOME, Category::TYPE_EXPENSE])],
            'group' => ['nullable', 'string', 'max:32', Rule::in([
                Category::GROUP_FIXED,
                Category::GROUP_VARIABLE,
                Category::GROUP_FINANCIAL,
            ])],
        ];
    }

    /**
     * @throws ValidationException
     */
    private function validateTypeAndGroup(string $type, ?string $group): void
    {
        if ($type === Category::TYPE_INCOME && $group !== null && $group !== '') {
            throw ValidationException::withMessages([
                'group' => 'Categorias de receita não utilizam subgrupo (fixa / variável / financeira).',
            ]);
        }
    }
}
