<?php

declare(strict_types=1);

namespace App\Modules\CreditCard\Services;

use App\Modules\CreditCard\Models\CreditCard;

final class CreditCardService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function listForUser(int $userId): array
    {
        return CreditCard::forUser($userId)
            ->orderBy('name')
            ->get()
            ->map(fn (CreditCard $c) => $this->toArray($c))
            ->all();
    }

    /**
     * @param  array{name: string, limit: float|int|string, closing_day: int, due_day: int}  $data
     */
    public function create(int $userId, array $data): CreditCard
    {
        return CreditCard::create([
            'user_id' => $userId,
            'name' => $data['name'],
            'credit_limit' => $data['limit'],
            'closing_day' => $data['closing_day'],
            'due_day' => $data['due_day'],
        ]);
    }

    /**
     * @param  array{name: string, limit: float|int|string, closing_day: int, due_day: int}  $data
     */
    public function update(CreditCard $card, array $data): CreditCard
    {
        $card->update([
            'name' => $data['name'],
            'credit_limit' => $data['limit'],
            'closing_day' => $data['closing_day'],
            'due_day' => $data['due_day'],
        ]);

        return $card->fresh();
    }

    public function delete(CreditCard $card): void
    {
        $card->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(CreditCard $c): array
    {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'limit' => round((float) $c->credit_limit, 2),
            'closing_day' => (int) $c->closing_day,
            'due_day' => (int) $c->due_day,
        ];
    }
}
