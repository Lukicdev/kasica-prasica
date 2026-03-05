<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Enums\TransactionType;
use App\Models\User;

final class GetDashboardDataAction
{
    /**
     * Get dashboard data for the user.
     *
     * @return array{expenseByCategory: array<int, array{name: string, total: float, color: string}>}
     */
    public function handle(User $user): array
    {
        $expenseByCategory = $user->transactions()
            ->where('transactions.type', TransactionType::Expense)
            ->whereNotNull('transactions.category_id')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as category_name, categories.color, sum(transactions.amount) as total')
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->category_name,
                'total' => (float) $row->total,
                'color' => $row->color ?? '#94a3b8',
            ])
            ->values()
            ->all();

        return [
            'expenseByCategory' => $expenseByCategory,
        ];
    }
}
