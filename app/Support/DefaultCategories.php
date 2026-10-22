<?php

namespace App\Support;

use App\Enums\CategoryKind;

/**
 * The categories a workspace starts with, taken from the household spreadsheet.
 */
final class DefaultCategories
{
    /**
     * @return list<array{name: string, description: string|null, icon: string, color: string}>
     */
    public static function forKind(CategoryKind $kind): array
    {
        return match ($kind) {
            CategoryKind::Income => [
                ['name' => 'Salary', 'description' => null, 'icon' => 'briefcase', 'color' => 'green'],
                ['name' => 'Bonus', 'description' => null, 'icon' => 'circle-dollar-sign', 'color' => 'amber'],
                ['name' => 'Gifts and other income', 'description' => null, 'icon' => 'gift', 'color' => 'pink'],
            ],
            CategoryKind::Expense => [
                ['name' => 'Mortgage', 'description' => null, 'icon' => 'house', 'color' => 'sky'],
                ['name' => 'Utilities & Property Tax', 'description' => null, 'icon' => 'zap', 'color' => 'yellow'],
                ['name' => 'Internet', 'description' => null, 'icon' => 'wifi', 'color' => 'blue'],
                ['name' => 'Groceries', 'description' => null, 'icon' => 'shopping-cart', 'color' => 'lime'],
                ['name' => 'Dining Out', 'description' => null, 'icon' => 'utensils', 'color' => 'orange'],
                ['name' => 'Debt', 'description' => null, 'icon' => 'landmark', 'color' => 'violet'],
                ['name' => 'Beauty', 'description' => null, 'icon' => 'sparkles', 'color' => 'fuchsia'],
                ['name' => 'Transportation', 'description' => null, 'icon' => 'car', 'color' => 'teal'],
                ['name' => 'A1 (mobile provider)', 'description' => 'Mobile provider', 'icon' => 'smartphone', 'color' => 'blue'],
                ['name' => 'Subscriptions', 'description' => null, 'icon' => 'repeat', 'color' => 'violet'],
                ['name' => 'Personal care (Nails/Haircuts)', 'description' => null, 'icon' => 'scissors', 'color' => 'fuchsia'],
                ['name' => 'Shopping', 'description' => null, 'icon' => 'shopping-bag', 'color' => 'orange'],
                ['name' => 'Extra expenses (Gifts, miscellaneous, etc.)', 'description' => null, 'icon' => 'package', 'color' => 'amber'],
                ['name' => 'Medical', 'description' => null, 'icon' => 'heart-pulse', 'color' => 'rose'],
            ],
        };
    }
}
