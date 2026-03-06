<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Transaction\CreateTransactionAction;
use App\Actions\Transaction\DeleteTransactionAction;
use App\Actions\Transaction\GetCreateTransactionDataAction;
use App\Actions\Transaction\GetEditTransactionDataAction;
use App\Actions\Transaction\ListTransactionsAction;
use App\Actions\Transaction\UpdateTransactionAction;
use App\Http\Requests\Transaction\CreateTransactionRequest;
use App\Http\Requests\Transaction\DeleteTransactionRequest;
use App\Http\Requests\Transaction\IndexTransactionRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
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
    public function index(IndexTransactionRequest $request, ListTransactionsAction $action): Response
    {
        $data = $action->handle($request->user(), $request->validated());

        return Inertia::render('transactions/index', $data);
    }

    /**
     * Show the form for creating a new transaction.
     */
    public function create(Request $request, GetCreateTransactionDataAction $action): Response
    {
        $data = $action->handle($request->user());

        return Inertia::render('transactions/create', $data);
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
    public function edit(Request $request, Transaction $transaction, GetEditTransactionDataAction $action): Response
    {
        $data = $action->handle($request->user(), $transaction);

        return Inertia::render('transactions/edit', $data);
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
