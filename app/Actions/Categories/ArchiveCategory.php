<?php

namespace App\Actions\Categories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class ArchiveCategory
{
    public function __construct(private ReorderCategories $reorder) {}

    public function handle(Category $category, User $actor): void
    {
        $this->guard($actor, $category);

        if ($category->archived_at !== null) {
            return;
        }

        DB::transaction(function () use ($category, $actor): void {
            $category->update([
                'archived_at' => now(),
                'position' => 100_000,
                'updated_by' => $actor->id,
            ]);

            $workspace = $actor->workspace;

            $this->reorder->handle(
                $workspace,
                $category->kind,
                $this->reorder->activeIds($workspace, $category->kind),
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
