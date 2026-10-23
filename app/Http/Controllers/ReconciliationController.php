<?php

namespace App\Http\Controllers;

use App\Actions\Reconciliations\CreateAdjustment;
use App\Actions\Reconciliations\UpdateAdjustment;
use App\Catalogs\ReconciliationCatalog;
use App\Enums\TransactionType;
use App\Http\Requests\Reconciliations\StoreReconciliationRequest;
use App\Http\Requests\Reconciliations\UpdateAdjustmentRequest;
use App\Models\Account;
use App\Models\Transaction;
use App\Money\AccountBalance;
use App\Money\MoneyFormatter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReconciliationController extends Controller
{
    public function __construct(
        private ReconciliationCatalog $catalog,
        private AccountBalance $balances,
        private MoneyFormatter $formatter,
    ) {}

    public function create(Request $request, Account $account): Response
    {
        Gate::authorize('view', $account);

        return Inertia::render('reconcile/create', $this->catalog->createForm($request->user(), $account));
    }

    public function store(StoreReconciliationRequest $request, Account $account, CreateAdjustment $create): RedirectResponse
    {
        $adjustment = $create->handle($account, $request->user(), $request->adjustmentAttributes());

        $this->flashBalance($adjustment->account, 'Saved.');

        return to_route('transactions.index', [
            'month' => $adjustment->booked_on->format('Y-m'),
        ]);
    }

    public function edit(Request $request, Transaction $transaction): Response
    {
        Gate::authorize('update', $transaction);
        abort_unless($transaction->type === TransactionType::Adjustment, 404);

        return Inertia::render('reconcile/edit', $this->catalog->editForm($request->user(), $transaction));
    }

    public function update(UpdateAdjustmentRequest $request, Transaction $transaction, UpdateAdjustment $update): RedirectResponse
    {
        $update->handle(
            $transaction,
            $request->account(),
            $request->user(),
            $request->adjustmentAttributes(),
        );

        $this->flashBalance($transaction->account, 'Saved.');

        return to_route('transactions.show', $transaction);
    }

    private function flashBalance(Account $account, string $prefix): void
    {
        $formatted = $this->formatter->format($this->balances->of($account), $account->currency);

        Inertia::flash('toast', ['type' => 'success', 'message' => $prefix.' '.$account->name.' is now '.$formatted.'.']);
    }
}
