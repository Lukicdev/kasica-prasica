<?php

namespace App\Actions\Categories;

use App\Enums\CategoryKind;
use App\Models\Category;
use App\Models\Workspace;
use App\Support\DefaultCategories;
use Illuminate\Support\Facades\Schema;

class SeedDefaultCategories
{
    public function handle(Workspace $workspace): void
    {
        if (! Schema::hasTable((new Category)->getTable())) {
            return;
        }

        if ($workspace->categories()->exists()) {
            return;
        }

        $iconColumn = Schema::hasColumn((new Category)->getTable(), 'icon');

        foreach (CategoryKind::ordered() as $kind) {
            foreach (DefaultCategories::forKind($kind) as $index => $category) {
                $workspace->categories()->create([
                    'name' => $category['name'],
                    'kind' => $kind,
                    'description' => $category['description'],
                    'position' => $index + 1,
                    ...($iconColumn ? ['icon' => $category['icon']] : []),
                ]);
            }
        }
    }
}
