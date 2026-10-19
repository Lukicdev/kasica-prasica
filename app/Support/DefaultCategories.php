<?php

namespace App\Support;

use App\Enums\CategoryKind;

/**
 * The categories a workspace starts with, taken from the household spreadsheet.
 */
final class DefaultCategories
{
    /**
     * @return list<array{name: string, description: string|null}>
     */
    public static function forKind(CategoryKind $kind): array
    {
        return match ($kind) {
            CategoryKind::Income => [
                ['name' => 'Salary', 'description' => null],
                ['name' => 'Bonus', 'description' => null],
                ['name' => 'Gifts and other income', 'description' => null],
            ],
            CategoryKind::Expense => [
                ['name' => 'Mortgage', 'description' => null],
                ['name' => 'Utilities & Property Tax', 'description' => null],
                ['name' => 'Internet', 'description' => null],
                ['name' => 'Groceries', 'description' => null],
                ['name' => 'Dining Out', 'description' => null],
                ['name' => 'Debt', 'description' => null],
                ['name' => 'Beauty', 'description' => null],
                ['name' => 'Transportation', 'description' => null],
                ['name' => 'A1 (mobile provider)', 'description' => 'Mobile provider'],
                ['name' => 'Subscriptions', 'description' => null],
                ['name' => 'Personal care (Nails/Haircuts)', 'description' => null],
                ['name' => 'Shopping', 'description' => null],
                ['name' => 'Extra expenses (Gifts, miscellaneous, etc.)', 'description' => null],
                ['name' => 'Medical', 'description' => null],
            ],
        };
    }
}
