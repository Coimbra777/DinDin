<?php

declare(strict_types=1);

namespace App\Policies\Finance;

use App\Models\Finance\Category;
use App\Models\User;

class CategoryPolicy
{
    public function update(User $user, Category $category): bool
    {
        return (int) $user->id === (int) $category->user_id;
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->update($user, $category);
    }
}
