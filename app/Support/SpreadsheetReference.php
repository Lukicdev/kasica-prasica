<?php

namespace App\Support;

/**
 * The household spreadsheet in section 6 of the product plan.
 *
 * Spent, budget and income are dinars. The sheet rounds a few figures;
 * these are the exact amounts the overview must reproduce. Shopping's
 * difference is −46.50; the sheet prints −47.
 */
final class SpreadsheetReference
{
    /**
     * The sheet does not name its month. Tests load the figures into this one.
     */
    public const string MONTH = '2026-09';

    public const string INCOME = '503354.00';

    public const string SPENT = '488076.54';

    public const string BUDGET = '455189.00';

    public const string DIFFERENCE = '32887.54';

    public const string LEFTOVER = '15277.46';

    public const string UNALLOCATED = '48165.00';

    /**
     * @return list<array{name: string, partner: 'one'|'two', amount: string}>
     */
    public static function income(): array
    {
        return [
            ['name' => 'Salary', 'partner' => 'one', 'amount' => '261434.00'],
            ['name' => 'Salary', 'partner' => 'two', 'amount' => '231920.00'],
            ['name' => 'Gifts and other income', 'partner' => 'one', 'amount' => '10000.00'],
        ];
    }

    /**
     * @return list<array{name: string, spent: string, budget: string}>
     */
    public static function expenses(): array
    {
        return [
            ['name' => 'Mortgage', 'spent' => '75649.00', 'budget' => '75700.00'],
            ['name' => 'Utilities & Property Tax', 'spent' => '17049.79', 'budget' => '12000.00'],
            ['name' => 'Internet', 'spent' => '4300.00', 'budget' => '4300.00'],
            ['name' => 'Groceries', 'spent' => '42608.00', 'budget' => '39649.00'],
            ['name' => 'Dining Out', 'spent' => '17903.00', 'budget' => '17903.00'],
            ['name' => 'Debt', 'spent' => '14797.00', 'budget' => '14798.00'],
            ['name' => 'Beauty', 'spent' => '11987.00', 'budget' => '10700.00'],
            ['name' => 'Transportation', 'spent' => '30826.00', 'budget' => '3500.00'],
            ['name' => 'A1 (mobile provider)', 'spent' => '4399.00', 'budget' => '2400.00'],
            ['name' => 'Subscriptions', 'spent' => '3467.16', 'budget' => '5400.00'],
            ['name' => 'Personal care (Nails/Haircuts)', 'spent' => '7600.00', 'budget' => '7600.00'],
            ['name' => 'Shopping', 'spent' => '26067.50', 'budget' => '26114.00'],
            ['name' => 'Extra expenses (Gifts, miscellaneous, etc.)', 'spent' => '199571.50', 'budget' => '229125.00'],
            ['name' => 'Medical', 'spent' => '31851.59', 'budget' => '6000.00'],
        ];
    }
}
