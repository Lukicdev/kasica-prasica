<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Transaction\CreateTransactionAction;
use App\Actions\Transaction\DeleteTransactionAction;
use App\Actions\Transaction\UpdateTransactionAction;
use App\Http\Requests\Transaction\CreateTransactionRequest;
use App\Http\Requests\Transaction\DeleteTransactionRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TransactionController extends Controller
{
    /**
     * Display a listing of transactions.
     */
    public function index(Request $request): Response
    {
        $transactions = $request->user()
            ->transactions()
            ->with(['account', 'category'])
            ->latest('transaction_date')
            ->latest('created_at')
            ->get();

        return Inertia::render('transactions/index', [
            'transactions' => $transactions,
        ]);
    }

    /**
     * Show the form for creating a new transaction.
     */
    public function create(Request $request): Response
    {
        $accounts = $request->user()->accounts()->get();
        $categories = Category::query()
            ->where(function ($query) use ($request) {
                $query->where('user_id', $request->user()->id)
                    ->orWhereNull('user_id');
            })
            ->get();

        return Inertia::render('transactions/create', [
            'accounts' => $accounts,
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created transaction.
     */
    public function store(CreateTransactionRequest $request, CreateTransactionAction $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated());

        return to_route('transactions.index');
    }

    /**
     * Display the specified transaction.
     */
    public function show(Transaction $transaction): Response
    {
        return Inertia::render('transactions/show', [
            'transaction' => $transaction->load(['account', 'category', 'relatedTransaction']),
        ]);
    }

    /**
     * Show the form for editing the specified transaction.
     */
    public function edit(Request $request, Transaction $transaction): Response
    {
        $accounts = $request->user()->accounts()->get();
        $categories = Category::query()
            ->where(function ($query) use ($request) {
                $query->where('user_id', $request->user()->id)
                    ->orWhereNull('user_id');
            })
            ->get();

        return Inertia::render('transactions/edit', [
            'transaction' => $transaction->load(['account', 'category']),
            'accounts' => $accounts,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified transaction.
     */
    public function update(UpdateTransactionRequest $request, Transaction $transaction, UpdateTransactionAction $action): RedirectResponse
    {
        $action->handle($transaction, $request->validated());

        return to_route('transactions.index');
    }

    /**
     * Remove the specified transaction.
     */
    public function destroy(DeleteTransactionRequest $request, Transaction $transaction, DeleteTransactionAction $action): RedirectResponse
    {
        $action->handle($transaction);

        return to_route('transactions.index');
    }
}
