<?php

namespace App\Support;

/**
 * Colors a person can assign to a category icon.
 * The same names are listed in resources/js/lib/category-colors.ts.
 */
final class CategoryColors
{
    /**
     * @var list<string>
     */
    public const NAMES = [
        'rose',
        'orange',
        'amber',
        'yellow',
        'lime',
        'green',
        'teal',
        'sky',
        'blue',
        'violet',
        'fuchsia',
        'pink',
    ];

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return self::NAMES;
    }
}
