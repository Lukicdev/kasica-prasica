<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Budget\CreateBudgetAction;
use App\Actions\Budget\DeleteBudgetAction;
use App\Actions\Budget\ListBudgetsAction;
use App\Actions\Budget\UpdateBudgetAction;
use App\Http\Requests\Budget\CreateBudgetRequest;
use App\Http\Requests\Budget\DeleteBudgetRequest;
use App\Http\Requests\Budget\UpdateBudgetRequest;
use App\Models\Budget;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class BudgetController extends Controller
{
    /**
     * Display a listing of budgets.
     */
    public function index(Request $request, ListBudgetsAction $action): Response
    {
        $budgets = $action->handle($request->user());

        return Inertia::render('budgets/index', [
            'budgets' => $budgets,
        ]);
    }

    /**
     * Show the form for creating a new budget.
     */
    public function create(): Response
    {
        return Inertia::render('budgets/create');
    }

    /**
     * Store a newly created budget.
     */
    public function store(CreateBudgetRequest $request, CreateBudgetAction $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated());

        return to_route('budgets.index');
    }

    /**
     * Display the specified budget.
     */
    public function show(Budget $budget): Response
    {
        return Inertia::render('budgets/show', [
            'budget' => $budget,
        ]);
    }

    /**
     * Show the form for editing the specified budget.
     */
    public function edit(Budget $budget): Response
    {
        return Inertia::render('budgets/edit', [
            'budget' => $budget,
        ]);
    }

    /**
     * Update the specified budget.
     */
    public function update(UpdateBudgetRequest $request, Budget $budget, UpdateBudgetAction $action): RedirectResponse
    {
        $action->handle($budget, $request->validated());

        return to_route('budgets.index');
    }

    /**
     * Remove the specified budget.
     */
    public function destroy(DeleteBudgetRequest $request, Budget $budget, DeleteBudgetAction $action): RedirectResponse
    {
        $action->handle($budget);

        return to_route('budgets.index');
    }
}
