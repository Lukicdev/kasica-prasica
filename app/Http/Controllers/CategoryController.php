<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Category\CreateCategoryAction;
use App\Actions\Category\DeleteCategoryAction;
use App\Actions\Category\UpdateCategoryAction;
use App\Http\Requests\Category\CreateCategoryRequest;
use App\Http\Requests\Category\DeleteCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index(Request $request): Response
    {
        $categories = Category::query()
            ->where(function ($query) use ($request) {
                $query->where('user_id', $request->user()->id)
                    ->orWhereNull('user_id');
            })
            ->latest()
            ->get();

        return Inertia::render('categories/index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Show the form for creating a new category.
     */
    public function create(): Response
    {
        return Inertia::render('categories/create');
    }

    /**
     * Store a newly created category.
     */
    public function store(CreateCategoryRequest $request, CreateCategoryAction $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated());

        return to_route('categories.index');
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category): Response
    {
        return Inertia::render('categories/show', [
            'category' => $category->load('user'),
        ]);
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(Category $category): Response
    {
        return Inertia::render('categories/edit', [
            'category' => $category,
        ]);
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateCategoryRequest $request, Category $category, UpdateCategoryAction $action): RedirectResponse
    {
        $action->handle($category, $request->validated());

        return to_route('categories.index');
    }

    /**
     * Remove the specified category.
     */
    public function destroy(DeleteCategoryRequest $request, Category $category, DeleteCategoryAction $action): RedirectResponse
    {
        $action->handle($category);

        return to_route('categories.index');
    }
}
