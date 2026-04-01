<?php

declare(strict_types=1);

namespace Tests\Unit\Policies\Finance;

use App\Models\Finance\Category;
use App\Models\Finance\Transaction;
use App\Models\User;
use App\Policies\Finance\TransactionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_delete_duplicate(): void
    {
        $user = User::factory()->create(['group_id' => 0]);
        $cat = Category::factory()->expense()->create(['user_id' => $user->id]);
        $tx = Transaction::factory()->forUserId((int) $user->id)->create(['category_id' => $cat->id]);

        $policy = new TransactionPolicy;
        $this->assertTrue($policy->update($user, $tx));
        $this->assertTrue($policy->delete($user, $tx));
        $this->assertTrue($policy->duplicate($user, $tx));
    }

    public function test_other_user_cannot_mutate_transaction(): void
    {
        $owner = User::factory()->create(['group_id' => 0]);
        $other = User::factory()->create(['group_id' => 0]);
        $cat = Category::factory()->expense()->create(['user_id' => $owner->id]);
        $tx = Transaction::factory()->forUserId((int) $owner->id)->create(['category_id' => $cat->id]);

        $policy = new TransactionPolicy;
        $this->assertFalse($policy->update($other, $tx));
        $this->assertFalse($policy->delete($other, $tx));
    }
}
