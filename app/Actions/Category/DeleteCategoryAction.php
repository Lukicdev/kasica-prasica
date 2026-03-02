<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Models\Category;

final class DeleteCategoryAction
{
    /**
     * Delete a category.
     */
    public function handle(Category $category): void
    {
        $category->delete();
    }
}
