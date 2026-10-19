<?php

namespace App\Enums;

enum CategoryKind: string
{
    case Income = 'income';
    case Expense = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Income',
            self::Expense => 'Expenses',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::Income => 0,
            self::Expense => 1,
        };
    }

    /**
     * @return list<self>
     */
    public static function ordered(): array
    {
        $cases = self::cases();

        usort($cases, fn (self $left, self $right): int => $left->order() <=> $right->order());

        return $cases;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $kind): array => [
            'value' => $kind->value,
            'label' => $kind->label(),
        ], self::ordered());
    }
}
