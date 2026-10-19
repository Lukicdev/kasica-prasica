<?php

namespace App\Enums;

enum AccountType: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case Card = 'card';
    case Savings = 'savings';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Bank => 'Bank account',
            self::Card => 'Card',
            self::Savings => 'Savings',
            self::Other => 'Other',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::Cash => 0,
            self::Bank => 1,
            self::Card => 2,
            self::Savings => 3,
            self::Other => 4,
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        $cases = self::cases();

        usort($cases, fn (self $left, self $right): int => $left->order() <=> $right->order());

        return array_map(fn (self $type): array => [
            'value' => $type->value,
            'label' => $type->label(),
        ], $cases);
    }
}
