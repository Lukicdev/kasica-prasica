<?php

namespace App\Http\Controllers;

use App\Actions\Budgets\StartBudgetMonth;
use App\Actions\Budgets\UpdateCategoryBudget;
use App\Http\Requests\Budgets\StoreBudgetMonthRequest;
use App\Http\Requests\Budgets\UpdateCategoryBudgetRequest;
use App\Models\BudgetMonth;
use App\Support\BudgetCatalog;
use App\Support\CalendarMonth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function __construct(private BudgetCatalog $catalog) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', BudgetMonth::class);

        return Inertia::render('budget/index', $this->catalog->page(
            $request->user(),
            CalendarMonth::fromQuery($request->string('month')->toString()),
        ));
    }

    public function store(StoreBudgetMonthRequest $request, StartBudgetMonth $start): RedirectResponse
    {
        $month = $request->month();

        $start->handle($request->user(), $month, $request->boolean('copy'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $request->boolean('copy')
                ? 'Budget copied from the previous month.'
                : 'Budget started.',
        ]);

        return to_route('budget.index', ['month' => $month->value()]);
    }

    public function update(UpdateCategoryBudgetRequest $request, UpdateCategoryBudget $update): RedirectResponse
    {
        $update->handle(
            $request->user(),
            $request->month(),
            $request->category(),
            $request->amount(),
        );

        return back();
    }
}
