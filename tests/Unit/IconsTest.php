<?php

use App\Enums\CategoryKind;
use App\Support\CategoryColors;
use App\Support\DefaultCategories;
use App\Support\Icons;
use Tests\TestCase;

uses(TestCase::class);

test('the icon catalog matches the picker', function () {
    $source = file_get_contents(resource_path('js/lib/icons.ts'));

    expect($source)->toBeString();

    preg_match('/export const iconNames = \[(.*?)\] as const;/s', (string) $source, $matches);

    expect($matches)->not->toBe([]);

    preg_match_all("/'([^']+)'/", $matches[1], $names);

    expect($names[1])->toBe(Icons::names());
});

test('default category icons come from the catalog', function () {
    $icons = [];

    foreach (CategoryKind::cases() as $kind) {
        foreach (DefaultCategories::forKind($kind) as $category) {
            $icons[] = $category['icon'];
        }
    }

    expect($icons)->each->toBeIn(Icons::names())
        ->and(array_unique($icons))->toHaveCount(count($icons));
});

test('the color catalog matches the picker', function () {
    $source = file_get_contents(resource_path('js/lib/category-colors.ts'));

    expect($source)->toBeString();

    preg_match('/export const categoryColorNames = \[(.*?)\] as const;/s', (string) $source, $matches);

    expect($matches)->not->toBe([]);

    preg_match_all("/'([^']+)'/", $matches[1], $names);

    expect($names[1])->toBe(CategoryColors::names());
});

test('default category colors come from the catalog', function () {
    $colors = [];

    foreach (CategoryKind::cases() as $kind) {
        foreach (DefaultCategories::forKind($kind) as $category) {
            $colors[] = $category['color'];
        }
    }

    expect($colors)->each->toBeIn(CategoryColors::names());
});
