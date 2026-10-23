<?php

namespace Database\Seeders\Data;

final class HouseholdPlan
{
    /**
     * @return list<array{name: string, partner: 'one'|'two', amount: string, day: int}>
     */
    public static function incomes(int $monthIndex): array
    {
        $rows = [
            ['name' => 'Salary', 'partner' => 'one', 'amount' => '261434.00', 'day' => 1],
            ['name' => 'Salary', 'partner' => 'two', 'amount' => '231920.00', 'day' => 5],
        ];

        if ($monthIndex % 2 === 1) {
            $rows[] = $monthIndex === 1
                ? ['name' => 'Bonus', 'partner' => 'one', 'amount' => '40000.00', 'day' => 15]
                : ['name' => 'Gifts and other income', 'partner' => 'two', 'amount' => '10000.00', 'day' => 18];
        }

        return $rows;
    }

    /**
     * The expense plan, close to a typical month so some categories land over it.
     *
     * @return array<string, string>
     */
    public static function budgets(): array
    {
        return [
            'Mortgage' => '75700.00',
            'Utilities & Property Tax' => '11000.00',
            'Internet' => '4300.00',
            'Groceries' => '20000.00',
            'Dining Out' => '7500.00',
            'Debt' => '14800.00',
            'Beauty' => '3000.00',
            'Transportation' => '7000.00',
            'A1 (mobile provider)' => '2400.00',
            'Subscriptions' => '2500.00',
            'Personal care (Nails/Haircuts)' => '2500.00',
            'Shopping' => '10000.00',
            'Extra expenses (Gifts, miscellaneous, etc.)' => '6000.00',
            'Medical' => '2000.00',
        ];
    }

    /**
     * Everyday spending for one month. Repeated across the four seeded months.
     *
     * @return list<array{name: string, amount: string, day: int, account: 'cash'|'bank', partner: 'one'|'two', note: string, varies: bool}>
     */
    public static function expenses(): array
    {
        return [
            ['name' => 'Mortgage', 'amount' => '75649.00', 'day' => 4, 'account' => 'bank', 'partner' => 'one', 'note' => 'Mortgage payment', 'varies' => false],
            ['name' => 'Utilities & Property Tax', 'amount' => '9200.00', 'day' => 7, 'account' => 'bank', 'partner' => 'one', 'note' => 'Electricity', 'varies' => true],
            ['name' => 'Utilities & Property Tax', 'amount' => '2800.00', 'day' => 11, 'account' => 'bank', 'partner' => 'two', 'note' => 'Water and heating', 'varies' => true],
            ['name' => 'Internet', 'amount' => '4300.00', 'day' => 6, 'account' => 'bank', 'partner' => 'one', 'note' => 'Internet', 'varies' => false],
            ['name' => 'Groceries', 'amount' => '4200.00', 'day' => 2, 'account' => 'cash', 'partner' => 'two', 'note' => 'Maxi', 'varies' => true],
            ['name' => 'Groceries', 'amount' => '6150.00', 'day' => 8, 'account' => 'bank', 'partner' => 'one', 'note' => 'Idea', 'varies' => true],
            ['name' => 'Groceries', 'amount' => '3890.00', 'day' => 14, 'account' => 'cash', 'partner' => 'two', 'note' => 'Lidl', 'varies' => true],
            ['name' => 'Groceries', 'amount' => '5400.00', 'day' => 19, 'account' => 'bank', 'partner' => 'one', 'note' => 'Maxi', 'varies' => true],
            ['name' => 'Groceries', 'amount' => '2100.00', 'day' => 22, 'account' => 'cash', 'partner' => 'two', 'note' => 'Market', 'varies' => true],
            ['name' => 'Dining Out', 'amount' => '4500.00', 'day' => 9, 'account' => 'bank', 'partner' => 'one', 'note' => 'Dinner', 'varies' => true],
            ['name' => 'Dining Out', 'amount' => '890.00', 'day' => 16, 'account' => 'cash', 'partner' => 'two', 'note' => 'Coffee', 'varies' => true],
            ['name' => 'Dining Out', 'amount' => '1650.00', 'day' => 21, 'account' => 'cash', 'partner' => 'one', 'note' => 'Takeaway', 'varies' => true],
            ['name' => 'Debt', 'amount' => '14797.00', 'day' => 10, 'account' => 'bank', 'partner' => 'one', 'note' => 'Loan payment', 'varies' => false],
            ['name' => 'Beauty', 'amount' => '3500.00', 'day' => 13, 'account' => 'cash', 'partner' => 'two', 'note' => 'Salon', 'varies' => true],
            ['name' => 'Transportation', 'amount' => '6500.00', 'day' => 5, 'account' => 'bank', 'partner' => 'one', 'note' => 'Fuel', 'varies' => true],
            ['name' => 'Transportation', 'amount' => '1800.00', 'day' => 18, 'account' => 'cash', 'partner' => 'two', 'note' => 'Bus pass', 'varies' => true],
            ['name' => 'A1 (mobile provider)', 'amount' => '2400.00', 'day' => 3, 'account' => 'bank', 'partner' => 'one', 'note' => 'Mobile bill', 'varies' => false],
            ['name' => 'Subscriptions', 'amount' => '1890.00', 'day' => 1, 'account' => 'bank', 'partner' => 'two', 'note' => 'Streaming', 'varies' => false],
            ['name' => 'Personal care (Nails/Haircuts)', 'amount' => '2500.00', 'day' => 15, 'account' => 'cash', 'partner' => 'two', 'note' => 'Haircut', 'varies' => true],
            ['name' => 'Shopping', 'amount' => '7800.00', 'day' => 12, 'account' => 'bank', 'partner' => 'one', 'note' => 'Clothes', 'varies' => true],
            ['name' => 'Shopping', 'amount' => '3200.00', 'day' => 20, 'account' => 'cash', 'partner' => 'two', 'note' => 'Household', 'varies' => true],
            ['name' => 'Extra expenses (Gifts, miscellaneous, etc.)', 'amount' => '4500.00', 'day' => 17, 'account' => 'cash', 'partner' => 'one', 'note' => 'Gift', 'varies' => true],
            ['name' => 'Medical', 'amount' => '2200.00', 'day' => 20, 'account' => 'bank', 'partner' => 'two', 'note' => 'Pharmacy', 'varies' => true],
        ];
    }
}
