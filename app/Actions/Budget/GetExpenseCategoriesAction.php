<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class GetExpenseCategoriesAction
{
    /**
     * Get expense categories for the user (user's and global).
     *
     * @return Collection<int, Category>
     */
    public function handle(User $user): Collection
    {
        return Category::query()
            ->where('type', CategoryType::Expense)
            ->where(fn ($query) => $query->where('user_id', $user->id)->orWhereNull('user_id'))
            ->orderBy('name')
            ->get();
    }
}
