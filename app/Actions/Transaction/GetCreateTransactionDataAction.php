<?php

declare(strict_types=1);

namespace App\Actions\Transaction;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class GetCreateTransactionDataAction
{
    /**
     * Get accounts and categories for the create transaction form.
     *
     * @return array{accounts: Collection, categories: Collection}
     */
    public function handle(User $user): array
    {
        $accounts = $user->accounts()->get();
        $categories = Category::query()
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereNull('user_id');
            })
            ->get();

        return [
            'accounts' => $accounts,
            'categories' => $categories,
        ];
    }
}
