<?php

namespace App\Actions\Categories;

use App\Enums\CategoryKind;
use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateCategory
{
    public function __construct(private ReorderCategories $reorder) {}

    /**
     * @param  array{name: string, icon: string|null, kind: CategoryKind, description: string|null}  $attributes
     */
    public function handle(Category $category, User $actor, array $attributes): Category
    {
        $this->guard($actor, $category);

        if ($category->isInUse() && $attributes['kind'] !== $category->kind) {
            throw ValidationException::withMessages([
                'kind' => 'Kind cannot change once the category has history.',
            ]);
        }

        $previousKind = $category->kind;
        $kindChanged = $previousKind !== $attributes['kind'];

        DB::transaction(function () use ($category, $actor, $attributes, $previousKind, $kindChanged): void {
            $category->update([
                'name' => $attributes['name'],
                'icon' => $attributes['icon'],
                'kind' => $attributes['kind'],
                'description' => $attributes['description'],
                'position' => $kindChanged ? 100_000 : $category->position,
                'updated_by' => $actor->id,
            ]);

            if (! $kindChanged) {
                return;
            }

            $workspace = $actor->workspace;

            $this->reorder->handle(
                $workspace,
                $previousKind,
                $this->reorder->activeIds($workspace, $previousKind),
            );
            $this->reorder->handle(
                $workspace,
                $attributes['kind'],
                $this->reorder->activeIds($workspace, $attributes['kind']),
            );
        });

        return $category->refresh();
    }

    private function guard(User $actor, Category $category): void
    {
        if ($actor->workspace_id !== $category->workspace_id) {
            throw new AuthorizationException('This category is outside your workspace.');
        }
    }
}
