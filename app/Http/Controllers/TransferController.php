<?php

namespace App\Http\Controllers;

use App\Actions\Transfers\CreateTransfer;
use App\Actions\Transfers\UpdateTransfer;
use App\Enums\TransactionType;
use App\Http\Requests\Transfers\StoreTransferRequest;
use App\Http\Requests\Transfers\UpdateTransferRequest;
use App\Models\Transaction;
use App\Support\AccountBalance;
use App\Support\MoneyFormatter;
use App\Support\TransferCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TransferController extends Controller
{
    public function __construct(
        private TransferCatalog $catalog,
        private AccountBalance $balances,
        private MoneyFormatter $formatter,
    ) {}

    public function create(Request $request): Response
    {
        Gate::authorize('create', Transaction::class);

        return Inertia::render('transfers/create', $this->catalog->form($request->user(), null, [
            'from' => $request->filled('from') ? $request->integer('from') : null,
            'to' => $request->filled('to') ? $request->integer('to') : null,
            'date' => $request->string('date')->toString(),
        ]));
    }

    public function store(StoreTransferRequest $request, CreateTransfer $create): RedirectResponse
    {
        $transfer = $create->handle(
            $request->fromAccount(),
            $request->toAccount(),
            $request->user(),
            $request->transferAttributes(),
        );

        $this->flashBalances($transfer, 'Saved.');

        return to_route('transactions.show', $transfer);
    }

    public function edit(Request $request, Transaction $transaction): Response
    {
        Gate::authorize('update', $transaction);
        abort_unless($transaction->type === TransactionType::Transfer, 404);

        return Inertia::render('transfers/edit', $this->catalog->form($request->user(), $transaction));
    }

    public function update(UpdateTransferRequest $request, Transaction $transaction, UpdateTransfer $update): RedirectResponse
    {
        $update->handle(
            $transaction,
            $request->fromAccount(),
            $request->toAccount(),
            $request->user(),
            $request->transferAttributes(),
        );

        $this->flashBalances($transaction->refresh(), 'Saved.');

        return to_route('transactions.show', $transaction);
    }

    private function flashBalances(Transaction $transfer, string $prefix): void
    {
        $transfer->loadMissing(['account', 'destinationAccount']);
        $from = $transfer->account;
        $to = $transfer->destinationAccount;
        $fromFormatted = $this->formatter->format($this->balances->of($from), $from->currency);
        $toFormatted = $to === null
            ? null
            : $this->formatter->format($this->balances->of($to), $to->currency);
        $message = $prefix.' '.$from->name.' is now '.$fromFormatted.'.';

        if ($to !== null && $toFormatted !== null) {
            $message .= ' '.$to->name.' is now '.$toFormatted.'.';
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);
    }
}
