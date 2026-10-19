<?php

namespace App\Actions\Categories;

use App\Enums\CategoryKind;
use App\Models\Category;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReorderCategories
{
    /**
     * Active categories take the given order. Archived ones stay after them,
     * in the order they already had.
     *
     * @param  list<int>  $activeIds
     */
    public function handle(Workspace $workspace, CategoryKind $kind, array $activeIds): void
    {
        $archivedIds = $this->ids($workspace, $kind, archived: true);

        DB::transaction(function () use ($activeIds, $archivedIds): void {
            foreach ([...$activeIds, ...$archivedIds] as $index => $id) {
                Category::query()->whereKey($id)->update([
                    'position' => $index + 1,
                ]);
            }
        });
    }

    /**
     * @return list<int>
     */
    public function activeIds(Workspace $workspace, CategoryKind $kind): array
    {
        return $this->ids($workspace, $kind, archived: false);
    }

    /**
     * @return list<int>
     */
    private function ids(Workspace $workspace, CategoryKind $kind, bool $archived): array
    {
        $ids = [];

        foreach (Category::query()
            ->whereBelongsTo($workspace)
            ->where('kind', $kind)
            ->when(
                $archived,
                fn (Builder $query) => $query->whereNotNull('archived_at'),
                fn (Builder $query) => $query->whereNull('archived_at'),
            )
            ->ordered()
            ->pluck('id') as $id) {
            $ids[] = (int) $id;
        }

        return $ids;
    }
}
