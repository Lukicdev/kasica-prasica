<?php

namespace App\Enums;

enum AssetType: string
{
    case RealEstate = 'real_estate';
    case Vehicle = 'vehicle';
    case Valuable = 'valuable';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::RealEstate => 'Real estate',
            self::Vehicle => 'Vehicle',
            self::Valuable => 'Valuable',
            self::Other => 'Other',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::RealEstate => 0,
            self::Vehicle => 1,
            self::Valuable => 2,
            self::Other => 3,
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
