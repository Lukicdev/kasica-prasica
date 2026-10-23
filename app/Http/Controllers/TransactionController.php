<?php

namespace App\Http\Controllers;

use App\Actions\Transactions\CreateTransaction;
use App\Actions\Transactions\DeleteTransaction;
use App\Actions\Transactions\UpdateTransaction;
use App\Catalogs\TransactionCatalog;
use App\Enums\TransactionType;
use App\Http\Requests\Transactions\StoreTransactionRequest;
use App\Http\Requests\Transactions\UpdateTransactionRequest;
use App\Models\Account;
use App\Models\Transaction;
use App\Money\AccountBalance;
use App\Money\MoneyFormatter;
use App\Queries\TransactionFilters;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    public function __construct(
        private TransactionCatalog $catalog,
        private AccountBalance $balances,
        private MoneyFormatter $formatter,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Transaction::class);

        return Inertia::render('transactions/index', $this->catalog->list(
            $request->user(),
            TransactionFilters::fromRequest($request),
        ));
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Transaction::class);

        return Inertia::render('transactions/create', [
            ...$this->catalog->form($request->user(), null, [
                'type' => $request->string('type')->toString(),
                'account' => $request->filled('account') ? $request->integer('account') : null,
                'category' => $request->filled('category') ? $request->integer('category') : null,
                'date' => $request->string('date')->toString(),
            ]),
            'confirmation' => $this->confirmation($request),
        ]);
    }

    public function store(StoreTransactionRequest $request, CreateTransaction $create): RedirectResponse
    {
        $transaction = $create->handle(
            $request->account(),
            $request->user(),
            $request->transactionAttributes(),
        );

        $this->flashBalance($transaction->account, 'Saved.', $transaction->id);

        if ($request->boolean('another')) {
            return to_route('transactions.create', [
                'type' => $transaction->type->value,
                'account' => $transaction->account_id,
                'date' => $transaction->booked_on->toDateString(),
            ]);
        }

        return to_route('transactions.index', [
            'month' => $transaction->booked_on->format('Y-m'),
        ]);
    }

    public function show(Request $request, Transaction $transaction): Response
    {
        Gate::authorize('view', $transaction);

        return Inertia::render('transactions/show', [
            'transaction' => $this->catalog->detail($transaction, $request->user()),
        ]);
    }

    public function edit(Request $request, Transaction $transaction): Response|RedirectResponse
    {
        Gate::authorize('update', $transaction);

        if ($transaction->type === TransactionType::Transfer) {
            return to_route('transfers.edit', $transaction);
        }

        if ($transaction->type === TransactionType::Adjustment) {
            return to_route('adjustments.edit', $transaction);
        }

        return Inertia::render('transactions/edit', [
            ...$this->catalog->form($request->user(), $transaction),
            'confirmation' => null,
        ]);
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction, UpdateTransaction $update): RedirectResponse
    {
        $update->handle(
            $transaction,
            $request->account(),
            $request->user(),
            $request->transactionAttributes(),
        );

        $this->flashBalance($transaction->account, 'Saved.');

        return to_route('transactions.show', $transaction);
    }

    public function destroy(Request $request, Transaction $transaction, DeleteTransaction $delete): RedirectResponse
    {
        Gate::authorize('delete', $transaction);

        $account = $transaction->account;
        $month = $transaction->booked_on->format('Y-m');

        $delete->handle($transaction, $request->user());

        $this->flashBalance($account, 'Deleted.');

        return to_route('transactions.index', ['month' => $month]);
    }

    private function flashBalance(Account $account, string $prefix, ?int $transactionId = null): void
    {
        $formatted = $this->formatter->format($this->balances->of($account), $account->currency);
        $message = $prefix.' '.$account->name.' is now '.$formatted.'.';

        session()->flash('transaction_balance', [
            'id' => $transactionId,
            'account' => $account->name,
            'formatted' => $formatted,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);
    }

    /**
     * @return array{id: int|null, account: string, formatted: string}|null
     */
    private function confirmation(Request $request): ?array
    {
        $confirmation = $request->session()->pull('transaction_balance');

        if (
            ! is_array($confirmation)
            || ! is_string($confirmation['account'] ?? null)
            || ! is_string($confirmation['formatted'] ?? null)
        ) {
            return null;
        }

        return [
            'id' => is_int($confirmation['id'] ?? null) ? $confirmation['id'] : null,
            'account' => $confirmation['account'],
            'formatted' => $confirmation['formatted'],
        ];
    }
}
