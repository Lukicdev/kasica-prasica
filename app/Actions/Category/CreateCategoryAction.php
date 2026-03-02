<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Models\Category;
use App\Models\User;

final class CreateCategoryAction
{
    /**
     * Create a new category.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, array $data): Category
    {
        return Category::create([
            'user_id' => ($data['is_shared'] ?? false) ? null : $user->id,
            'name' => $data['name'],
            'type' => $data['type'],
            'color' => $data['color'],
        ]);
    }
}
