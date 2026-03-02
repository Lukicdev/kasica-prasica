<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Models\Category;

final class UpdateCategoryAction
{
    /**
     * Update a category.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Category $category, array $data): Category
    {
        if (isset($data['is_shared'])) {
            $data['user_id'] = $data['is_shared'] ? null : $category->user_id;
            unset($data['is_shared']);
        }

        $category->update($data);

        return $category->fresh();
    }
}
