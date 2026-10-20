<?php

namespace App\Http\Controllers;

use App\Actions\Recurrences\ConfirmRecurrence;
use App\Http\Requests\Recurrences\ConfirmRecurrenceRequest;
use App\Models\Account;
use App\Models\Transaction;
use App\Support\AccountBalance;
use App\Support\MoneyFormatter;
use App\Support\RecurrenceCatalog;
use App\Support\RecurringSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RecurrenceController extends Controller
{
    public function __construct(
        private RecurrenceCatalog $catalog,
        private RecurringSchedule $schedule,
        private AccountBalance $balances,
        private MoneyFormatter $formatter,
    ) {}

    public function edit(Request $request, Transaction $transaction): Response|RedirectResponse
    {
        Gate::authorize('update', $transaction);

        $month = $this->catalog->monthFrom($request->string('month')->toString());

        if (! $this->schedule->isDue($transaction, $month)) {
            abort(404);
        }

        $existing = $this->schedule->occurrence($transaction, $month);

        if ($existing instanceof Transaction) {
            return to_route('transactions.show', $existing);
        }

        return Inertia::render('recurrences/edit', $this->catalog->form($request->user(), $transaction, $month));
    }

    public function store(ConfirmRecurrenceRequest $request, Transaction $transaction, ConfirmRecurrence $confirm): RedirectResponse
    {
        $month = $request->month();

        if (! $this->schedule->isDue($transaction, $month)) {
            abort(404);
        }

        $existing = $this->schedule->occurrence($transaction, $month);

        if ($existing instanceof Transaction) {
            return to_route('transactions.show', $existing);
        }

        try {
            $created = $confirm->handle($transaction, $request->user(), $month, $request->changes());
        } catch (ValidationException $exception) {
            return to_route('recurrences.edit', [
                'transaction' => $transaction,
                'month' => $month->value(),
            ])->withErrors($exception->errors())->withInput();
        }

        $this->flashBalance($created->account, 'Saved.');

        return to_route('transactions.show', $created);
    }

    private function flashBalance(Account $account, string $prefix): void
    {
        $formatted = $this->formatter->format($this->balances->of($account), $account->currency);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $prefix.' '.$account->name.' is now '.$formatted.'.',
        ]);
    }
}
