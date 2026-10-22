<?php

namespace App\Actions\Categories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class DeleteCategory
{
    public function __construct(private ReorderCategories $reorder) {}

    public function handle(Category $category, User $actor): void
    {
        $this->guard($actor, $category);

        $workspace = $actor->workspace;
        $kind = $category->kind;

        DB::transaction(function () use ($category, $workspace, $kind): void {
            $category->delete();

            $this->reorder->handle(
                $workspace,
                $kind,
                $this->reorder->activeIds($workspace, $kind),
            );
        });
    }

    private function guard(User $actor, Category $category): void
    {
        if ($actor->workspace_id !== $category->workspace_id) {
            throw new AuthorizationException('This category is outside your workspace.');
        }
    }
}
