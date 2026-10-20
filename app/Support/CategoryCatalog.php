<?php

namespace App\Support;

use App\Enums\CategoryKind;
use App\Models\Category;
use Illuminate\Support\Collection;

/**
 * The category pages. Active categories are the ones you pick from. Archived
 * categories stay visible so their history still has a name.
 */
class CategoryCatalog
{
    /**
     * @param  Collection<int, Category>  $categories
     * @return array{
     *     groups: list<array{
     *         kind: string,
     *         label: string,
     *         categories: list<array{id: int, name: string, icon: string|null, kind: string, description: string|null, label: string, archived: bool}>,
     *         archived: list<array{id: int, name: string, icon: string|null, kind: string, description: string|null, label: string, archived: bool}>
     *     }>
     * }
     */
    public function list(Collection $categories): array
    {
        $groups = [];

        foreach (CategoryKind::ordered() as $kind) {
            $active = [];
            $archived = [];

            $ofKind = $categories
                ->filter(fn (Category $category): bool => $category->kind === $kind)
                ->sortBy(fn (Category $category): array => [
                    $category->archived_at === null ? 0 : 1,
                    $category->position,
                    $category->id,
                ]);

            foreach ($ofKind as $category) {
                $row = $this->summary($category);

                if ($category->archived_at === null) {
                    $active[] = $row;
                } else {
                    $archived[] = $row;
                }
            }

            $groups[] = [
                'kind' => $kind->value,
                'label' => $kind->label(),
                'categories' => $active,
                'archived' => $archived,
            ];
        }

        return [
            'groups' => $groups,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     icon: string|null,
     *     kind: string,
     *     description: string|null,
     *     label: string,
     *     kind_locked: bool,
     *     archived: bool,
     *     can_delete: bool,
     *     created_by: string|null,
     *     updated_by: string|null
     * }
     */
    public function form(Category $category): array
    {
        $this->ensureHistoryCounts($category);
        $category->loadMissing(['creator:id,name', 'editor:id,name']);
        $inUse = (int) $category->transactions_count > 0 || (int) $category->budgets_count > 0;
        $editor = $category->editor;
        $updatedBy = null;

        if (
            $editor !== null
            && $category->updated_at !== null
            && $category->created_at !== null
            && $category->updated_at->greaterThan($category->created_at)
        ) {
            $updatedBy = $editor->name;
        }

        return [
            ...$this->summary($category),
            'kind_locked' => $inUse,
            'can_delete' => ! $inUse,
            'created_by' => $category->creator?->name,
            'updated_by' => $updatedBy,
        ];
    }

    /**
     * @return array{id: int, name: string, icon: string|null, kind: string, description: string|null, label: string, archived: bool}
     */
    private function summary(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'icon' => $category->icon,
            'kind' => $category->kind->value,
            'description' => $category->description,
            'label' => $category->choiceLabel(),
            'archived' => $category->archived_at !== null,
        ];
    }

    private function ensureHistoryCounts(Category $category): void
    {
        if ($category->transactions_count === null || $category->budgets_count === null) {
            $category->loadCount(['transactions', 'budgets']);
        }
    }
}
