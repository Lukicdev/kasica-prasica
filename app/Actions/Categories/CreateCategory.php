<?php

namespace App\Actions\Categories;

use App\Enums\CategoryKind;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateCategory
{
    public function __construct(private ReorderCategories $reorder) {}

    /**
     * @param  array{name: string, icon: string|null, kind: CategoryKind, description: string|null}  $attributes
     */
    public function handle(User $actor, array $attributes): Category
    {
        return DB::transaction(function () use ($actor, $attributes): Category {
            $category = Category::query()->create([
                'workspace_id' => $actor->workspace_id,
                'name' => $attributes['name'],
                'icon' => $attributes['icon'],
                'kind' => $attributes['kind'],
                'description' => $attributes['description'],
                'position' => 100_000,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $workspace = $actor->workspace;

            $this->reorder->handle(
                $workspace,
                $attributes['kind'],
                $this->reorder->activeIds($workspace, $attributes['kind']),
            );

            return $category->refresh();
        });
    }
}
