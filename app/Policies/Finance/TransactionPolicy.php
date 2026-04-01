<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use App\Models\Finance\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function update(User $user, Transaction $transaction): bool
    {
        return (int) $user->id === (int) $transaction->user_id;
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return $this->update($user, $transaction);
    }

    public function duplicate(User $user, Transaction $transaction): bool
    {
        return $this->update($user, $transaction);
    }
}
