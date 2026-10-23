<?php

namespace App\Enums;

/**
 * Colors a person can assign to a category icon.
 * The same names are listed in resources/js/lib/category-colors.ts.
 */
enum CategoryColor: string
{
    case Rose = 'rose';
    case Orange = 'orange';
    case Amber = 'amber';
    case Yellow = 'yellow';
    case Lime = 'lime';
    case Green = 'green';
    case Teal = 'teal';
    case Sky = 'sky';
    case Blue = 'blue';
    case Violet = 'violet';
    case Fuchsia = 'fuchsia';
    case Pink = 'pink';
}
