<?php

namespace App\Support;

use App\Enums\CategoryColor;
use App\Enums\CategoryKind;
use App\Enums\Icon;

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
                ['name' => 'Salary', 'description' => null, 'icon' => Icon::Briefcase->value, 'color' => CategoryColor::Green->value],
                ['name' => 'Bonus', 'description' => null, 'icon' => Icon::CircleDollarSign->value, 'color' => CategoryColor::Amber->value],
                ['name' => 'Gifts and other income', 'description' => null, 'icon' => Icon::Gift->value, 'color' => CategoryColor::Pink->value],
            ],
            CategoryKind::Expense => [
                ['name' => 'Mortgage', 'description' => null, 'icon' => Icon::House->value, 'color' => CategoryColor::Sky->value],
                ['name' => 'Utilities & Property Tax', 'description' => null, 'icon' => Icon::Zap->value, 'color' => CategoryColor::Yellow->value],
                ['name' => 'Internet', 'description' => null, 'icon' => Icon::Wifi->value, 'color' => CategoryColor::Blue->value],
                ['name' => 'Groceries', 'description' => null, 'icon' => Icon::ShoppingCart->value, 'color' => CategoryColor::Lime->value],
                ['name' => 'Dining Out', 'description' => null, 'icon' => Icon::Utensils->value, 'color' => CategoryColor::Orange->value],
                ['name' => 'Debt', 'description' => null, 'icon' => Icon::Landmark->value, 'color' => CategoryColor::Violet->value],
                ['name' => 'Beauty', 'description' => null, 'icon' => Icon::Sparkles->value, 'color' => CategoryColor::Fuchsia->value],
                ['name' => 'Transportation', 'description' => null, 'icon' => Icon::Car->value, 'color' => CategoryColor::Teal->value],
                ['name' => 'A1 (mobile provider)', 'description' => 'Mobile provider', 'icon' => Icon::Smartphone->value, 'color' => CategoryColor::Blue->value],
                ['name' => 'Subscriptions', 'description' => null, 'icon' => Icon::Repeat->value, 'color' => CategoryColor::Violet->value],
                ['name' => 'Personal care (Nails/Haircuts)', 'description' => null, 'icon' => Icon::Scissors->value, 'color' => CategoryColor::Fuchsia->value],
                ['name' => 'Shopping', 'description' => null, 'icon' => Icon::ShoppingBag->value, 'color' => CategoryColor::Orange->value],
                ['name' => 'Extra expenses (Gifts, miscellaneous, etc.)', 'description' => null, 'icon' => Icon::Package->value, 'color' => CategoryColor::Amber->value],
                ['name' => 'Medical', 'description' => null, 'icon' => Icon::HeartPulse->value, 'color' => CategoryColor::Rose->value],
            ],
        };
    }
}
