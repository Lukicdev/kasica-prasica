<?php

namespace App\Http\Controllers;

use App\Actions\Categories\ArchiveCategory;
use App\Actions\Categories\CreateCategory;
use App\Actions\Categories\ReorderCategories;
use App\Actions\Categories\RestoreCategory;
use App\Actions\Categories\UpdateCategory;
use App\Enums\CategoryKind;
use App\Http\Requests\Categories\ReorderCategoriesRequest;
use App\Http\Requests\Categories\StoreCategoryRequest;
use App\Http\Requests\Categories\UpdateCategoryRequest;
use App\Models\Category;
use App\Support\CategoryCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(private CategoryCatalog $catalog) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        Gate::authorize('viewAny', Category::class);

        $categories = Category::query()
            ->whereBelongsTo($user->workspace)
            ->get();

        return Inertia::render('categories/index', $this->catalog->list($categories));
    }

    public function create(): Response
    {
        Gate::authorize('create', Category::class);

        return Inertia::render('categories/create', [
            'kinds' => CategoryKind::options(),
        ]);
    }

    public function store(StoreCategoryRequest $request, CreateCategory $create): RedirectResponse
    {
        $user = $request->user();
        $create->handle($user, $request->categoryAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category created.']);

        return to_route('categories.index');
    }

    public function edit(Category $category): Response
    {
        Gate::authorize('update', $category);

        return Inertia::render('categories/edit', [
            'category' => $this->catalog->form($category),
            'kinds' => CategoryKind::options(),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category, UpdateCategory $update): RedirectResponse
    {
        $user = $request->user();

        $update->handle($category, $user, $request->categoryAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category updated.']);

        return to_route('categories.edit', $category);
    }

    public function destroy(Request $request, Category $category, ReorderCategories $reorder): RedirectResponse
    {
        Gate::authorize('delete', $category);

        $user = $request->user();
        $workspace = $user->workspace;
        $kind = $category->kind;

        $category->delete();

        $reorder->handle($workspace, $kind, $reorder->activeIds($workspace, $kind));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category deleted.']);

        return to_route('categories.index');
    }

    public function archive(Request $request, Category $category, ArchiveCategory $archive): RedirectResponse
    {
        Gate::authorize('update', $category);

        $user = $request->user();

        $archive->handle($category, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category archived.']);

        return to_route('categories.edit', $category);
    }

    public function restore(Request $request, Category $category, RestoreCategory $restore): RedirectResponse
    {
        Gate::authorize('update', $category);

        $user = $request->user();

        $restore->handle($category, $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category restored.']);

        return to_route('categories.edit', $category);
    }

    public function reorder(ReorderCategoriesRequest $request, ReorderCategories $reorder): RedirectResponse
    {
        $user = $request->user();

        $reorder->handle(
            $user->workspace,
            $request->kind(),
            $request->ids(),
        );

        return back();
    }
}
