<?php

namespace App\Support;

/**
 * Lucide icons a person can assign to an account or a category.
 * The same names are listed in resources/js/lib/icons.ts.
 */
final class Icons
{
    /**
     * @var list<string>
     */
    public const NAMES = [
        'wallet',
        'piggy-bank',
        'banknote',
        'coins',
        'landmark',
        'credit-card',
        'receipt',
        'circle-dollar-sign',
        'hand-coins',
        'badge-percent',
        'house',
        'zap',
        'droplets',
        'flame',
        'wifi',
        'smartphone',
        'sofa',
        'key-round',
        'shopping-cart',
        'utensils',
        'coffee',
        'wine',
        'car',
        'bus',
        'fuel',
        'plane',
        'train-front',
        'bike',
        'sparkles',
        'scissors',
        'shirt',
        'heart',
        'heart-pulse',
        'stethoscope',
        'pill',
        'gift',
        'shopping-bag',
        'package',
        'repeat',
        'briefcase',
        'baby',
        'dog',
        'graduation-cap',
        'dumbbell',
        'film',
        'music',
        'gamepad-2',
        'palette',
        'wrench',
        'tags',
        'chart-column',
        'layout-grid',
    ];

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return self::NAMES;
    }
}
