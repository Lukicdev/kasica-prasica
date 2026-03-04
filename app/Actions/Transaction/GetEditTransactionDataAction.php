<?php

declare(strict_types=1);

namespace App\Actions\Transaction;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class GetEditTransactionDataAction
{
    /**
     * Get transaction, accounts and categories for the edit transaction form.
     *
     * @return array{transaction: Transaction, accounts: Collection, categories: Collection}
     */
    public function handle(User $user, Transaction $transaction): array
    {
        $transaction->load(['account', 'category']);

        $accounts = $user->accounts()->get();
        $categories = Category::query()
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            })
            ->get();

        return [
            'transaction' => $transaction,
            'accounts' => $accounts,
            'categories' => $categories,
        ];
    }
}
