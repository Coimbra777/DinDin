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
        $typeAfter = $data['type'] ?? $category->type ?? Category::TYPE_EXPENSE;
        $groupAfter = array_key_exists('group', $data) ? $data['group'] : $category->group;

        if ($typeAfter === Category::TYPE_INCOME) {
            $groupAfter = null;
            $data['group'] = null;
        }

        $this->validateTypeAndGroup($typeAfter, $groupAfter);

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
