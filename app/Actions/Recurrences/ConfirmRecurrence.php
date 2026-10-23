<?php

namespace App\Actions\Recurrences;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Money\Decimal;
use App\Money\MoneyConverter;
use App\Money\TransactionAmounts;
use App\Support\CalendarMonth;
use App\Support\RecurringSchedule;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmRecurrence
{
    public function __construct(
        private RecurringSchedule $schedule,
        private TransactionAmounts $amounts,
    ) {}

    /**
     * Turn one pending month into a real transaction. Omitted fields keep the
     * repeating transaction's amount, currency, account and day.
     *
     * @param  array{amount?: string, refund?: bool, currency?: string, account_id?: int, booked_on?: string}  $changes
     */
    public function handle(Transaction $template, User $actor, CalendarMonth $month, array $changes = []): Transaction
    {
        if ($actor->workspace_id !== $template->workspace_id) {
            throw new AuthorizationException('This transaction is outside your workspace.');
        }

        if (! $this->schedule->isDue($template, $month)) {
            throw ValidationException::withMessages([
                'month' => 'This repeat is not waiting for that month.',
            ]);
        }

        return DB::transaction(function () use ($template, $actor, $month, $changes): Transaction {
            $locked = Transaction::query()
                ->with(['category', 'person', 'account'])
                ->lockForUpdate()
                ->find($template->id);

            if (! $locked instanceof Transaction || ! $this->schedule->isDue($locked, $month)) {
                throw ValidationException::withMessages([
                    'month' => 'This repeat is not waiting for that month.',
                ]);
            }

            if ($this->schedule->occurrence($locked, $month) instanceof Transaction) {
                throw ValidationException::withMessages([
                    'month' => 'This month is already confirmed.',
                ]);
            }

            $account = $this->account($locked, $actor, $changes);
            $category = $locked->category;

            if (! $category instanceof Category || $category->workspace_id !== $locked->workspace_id) {
                throw ValidationException::withMessages([
                    'category_id' => 'Choose a category.',
                ]);
            }

            $person = $locked->person;
            if (! $person instanceof User || $person->workspace_id !== $locked->workspace_id) {
                $person = $actor;
            }

            $currency = $changes['currency'] ?? $locked->currency;
            $bookedOn = $this->bookedOn($locked, $month, $changes);
            $resolved = $this->amounts->resolve(
                $account,
                $this->amount($locked, $changes),
                $currency,
                $bookedOn,
            );

            $created = Transaction::query()->create([
                'workspace_id' => $locked->workspace_id,
                'account_id' => $account->id,
                'category_id' => $category->id,
                'person_id' => $person->id,
                'type' => $locked->type,
                'amount' => $resolved['amount'],
                'currency' => $currency,
                'account_amount' => $resolved['account_amount'],
                'base_amount' => $resolved['base_amount'],
                'exchange_rate' => $resolved['exchange_rate'],
                'booked_on' => $bookedOn->toDateString(),
                'note' => $locked->note,
                'repeats' => false,
                'recurrence_id' => $locked->id,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            return $created;
        });
    }

    /**
     * @param  array{amount?: string, refund?: bool, currency?: string, account_id?: int, booked_on?: string}  $changes
     */
    private function account(Transaction $template, User $actor, array $changes): Account
    {
        if (! array_key_exists('account_id', $changes)) {
            $account = $template->account;
        } else {
            $account = Account::query()
                ->whereBelongsTo($actor->workspace)
                ->find($changes['account_id']);
        }

        if (! $account instanceof Account) {
            throw ValidationException::withMessages([
                'account_id' => 'Choose an account.',
            ]);
        }

        if ($account->archived_at !== null) {
            throw ValidationException::withMessages([
                'account_id' => 'This account cannot be used for new transactions.',
            ]);
        }

        return $account;
    }

    /**
     * @param  array{amount?: string, refund?: bool, currency?: string, account_id?: int, booked_on?: string}  $changes
     */
    private function amount(Transaction $template, array $changes): string
    {
        if (! array_key_exists('amount', $changes)) {
            return Decimal::of((string) $template->amount)->toString(MoneyConverter::SCALE);
        }

        $amount = Decimal::of($changes['amount'])->toString(MoneyConverter::SCALE);
        $refund = $template->type === TransactionType::Expense && ($changes['refund'] ?? false);

        if ($refund && ! Decimal::of($amount)->isNegative()) {
            $amount = Decimal::of('0')->subtract(Decimal::of($amount))->toString(MoneyConverter::SCALE);
        }

        if (Decimal::of($amount)->isZero()) {
            throw ValidationException::withMessages([
                'amount' => 'Enter an amount other than zero.',
            ]);
        }

        if ($template->type === TransactionType::Income && Decimal::of($amount)->isNegative()) {
            throw ValidationException::withMessages([
                'amount' => 'Enter an income amount greater than zero.',
            ]);
        }

        return $amount;
    }

    /**
     * @param  array{amount?: string, refund?: bool, currency?: string, account_id?: int, booked_on?: string}  $changes
     */
    private function bookedOn(Transaction $template, CalendarMonth $month, array $changes): Carbon
    {
        if (! array_key_exists('booked_on', $changes)) {
            return Carbon::parse($this->schedule->date($template, $month)->toDateString());
        }

        $bookedOn = Carbon::parse($changes['booked_on'])->startOfDay();
        $date = $bookedOn->toDateString();

        if ($date < $month->start()->toDateString() || $date >= $month->endExclusive()->toDateString()) {
            throw ValidationException::withMessages([
                'booked_on' => 'Choose a date in '.$month->label().'.',
            ]);
        }

        return $bookedOn;
    }
}
