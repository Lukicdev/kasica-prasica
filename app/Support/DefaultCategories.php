<?php

namespace App\Support;

use App\Enums\CategoryKind;

/**
 * The categories a workspace starts with, taken from the household spreadsheet.
 */
final class DefaultCategories
{
    /**
     * @return list<array{name: string, description: string|null, icon: string}>
     */
    public static function forKind(CategoryKind $kind): array
    {
        return match ($kind) {
            CategoryKind::Income => [
                ['name' => 'Salary', 'description' => null, 'icon' => 'briefcase'],
                ['name' => 'Bonus', 'description' => null, 'icon' => 'circle-dollar-sign'],
                ['name' => 'Gifts and other income', 'description' => null, 'icon' => 'gift'],
            ],
            CategoryKind::Expense => [
                ['name' => 'Mortgage', 'description' => null, 'icon' => 'house'],
                ['name' => 'Utilities & Property Tax', 'description' => null, 'icon' => 'zap'],
                ['name' => 'Internet', 'description' => null, 'icon' => 'wifi'],
                ['name' => 'Groceries', 'description' => null, 'icon' => 'shopping-cart'],
                ['name' => 'Dining Out', 'description' => null, 'icon' => 'utensils'],
                ['name' => 'Debt', 'description' => null, 'icon' => 'landmark'],
                ['name' => 'Beauty', 'description' => null, 'icon' => 'sparkles'],
                ['name' => 'Transportation', 'description' => null, 'icon' => 'car'],
                ['name' => 'A1 (mobile provider)', 'description' => 'Mobile provider', 'icon' => 'smartphone'],
                ['name' => 'Subscriptions', 'description' => null, 'icon' => 'repeat'],
                ['name' => 'Personal care (Nails/Haircuts)', 'description' => null, 'icon' => 'scissors'],
                ['name' => 'Shopping', 'description' => null, 'icon' => 'shopping-bag'],
                ['name' => 'Extra expenses (Gifts, miscellaneous, etc.)', 'description' => null, 'icon' => 'package'],
                ['name' => 'Medical', 'description' => null, 'icon' => 'heart-pulse'],
            ],
        };
    }
}
