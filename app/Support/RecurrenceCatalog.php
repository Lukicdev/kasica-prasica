<?php

namespace App\Support;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;

/**
 * Pending repeats for a month, and the form that confirms one of them.
 *
 * A pending item is calculated, not stored, so it cannot change spending or
 * an account balance.
 */
class RecurrenceCatalog
{
    public function __construct(
        private RecurringSchedule $schedule,
        private MoneyConverter $converter,
        private AccountBalance $balances,
    ) {}

    public function monthFrom(string $value): CalendarMonth
    {
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value) !== 1) {
            abort(404);
        }

        return CalendarMonth::fromValue($value);
    }

    /**
     * @return list<array{
     *     id: int,
     *     month: string,
     *     date: string,
     *     date_label: string,
     *     type: string,
     *     type_label: string,
     *     category: string|null,
     *     note: string|null,
     *     account: string,
     *     person: string|null,
     *     amount: array{
     *         amount: string,
     *         currency: string,
     *         formatted: string,
     *         original_amount: string|null,
     *         original_currency: string|null,
     *         original_formatted: string|null
     *     },
     *     status_label: string
     * }>
     */
    public function pending(
        User $user,
        CalendarMonth $month,
        ?TransactionType $type = null,
        ?int $categoryId = null,
        ?int $accountId = null,
        ?int $personId = null,
        ?string $currency = null,
        string $search = '',
    ): array {
        if ($type instanceof TransactionType && ! $type->isIncomeOrExpense()) {
            return [];
        }

        $query = Transaction::query()
            ->whereBelongsTo($user->workspace)
            ->repeating()
            ->where('booked_on', '<', $month->start()->toDateString())
            ->with(['category:id,name', 'account:id,name,archived_at', 'person:id,name']);

        if ($type instanceof TransactionType) {
            $query->where('type', $type->value);
        } else {
            $query->whereIn('type', [TransactionType::Income->value, TransactionType::Expense->value]);
        }

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        if ($accountId !== null) {
            $query->where('account_id', $accountId);
        }

        if ($personId !== null) {
            $query->where('person_id', $personId);
        }

        if (is_string($currency)) {
            $query->where('currency', $currency);
        }

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->whereRaw('note like ? escape ?', [$like, '\\']);
        }

        $templates = $query->orderBy('id')->get();

        if ($templates->isEmpty()) {
            return [];
        }

        $confirmed = Transaction::query()
            ->whereIn('recurrence_id', $templates->modelKeys())
            ->where('booked_on', '>=', $month->start()->toDateString())
            ->where('booked_on', '<', $month->endExclusive()->toDateString())
            ->pluck('recurrence_id');

        $pending = [];

        foreach ($templates as $template) {
            if ($confirmed->contains($template->id) || ! $this->schedule->isDue($template, $month)) {
                continue;
            }

            $pending[] = $this->present($template, $month, $user);
        }

        usort($pending, function (array $left, array $right): int {
            return [$left['date'], $left['category'] ?? '', $left['id']]
                <=> [$right['date'], $right['category'] ?? '', $right['id']];
        });

        return $pending;
    }

    /**
     * @return array{
     *     recurrence: array{
     *         id: int,
     *         month: string,
     *         month_label: string,
     *         type: string,
     *         type_label: string,
     *         category: string|null,
     *         person: string|null,
     *         note: string|null,
     *         amount: string,
     *         refund: bool,
     *         currency: string,
     *         exchange_rate: string,
     *         account_id: int,
     *         booked_on: string,
     *         min_date: string,
     *         max_date: string
     *     },
     *     accounts: list<array{id: int, name: string, icon: string|null, currency: string, balance: string, archived: bool}>,
     *     needs_account: bool
     * }
     */
    public function form(User $user, Transaction $template, CalendarMonth $month): array
    {
        $template->loadMissing(['category', 'person', 'account']);

        $accounts = Account::query()
            ->whereBelongsTo($user->workspace)
            ->orderBy('name')
            ->get()
            ->filter(function (Account $account) use ($template): bool {
                if ($account->id === $template->account_id) {
                    return true;
                }

                return $account->archived_at === null;
            })
            ->values();

        $balances = $this->balances->forAccounts($accounts);
        $amount = Decimal::of((string) $template->amount);
        $absolute = $amount->isNegative()
            ? Decimal::of('0')->subtract($amount)->toString(MoneyConverter::SCALE)
            : $amount->toString(MoneyConverter::SCALE);
        $date = $this->schedule->date($template, $month);

        return [
            'recurrence' => [
                'id' => $template->id,
                'month' => $month->value(),
                'month_label' => $month->label(),
                'type' => $template->type->value,
                'type_label' => $template->typeLabel(),
                'category' => $template->category?->choiceLabel(),
                'person' => $template->person?->name,
                'note' => $template->note,
                'amount' => $absolute,
                'refund' => $template->isRefund(),
                'currency' => $template->currency,
                'exchange_rate' => Rate::trim((string) $template->exchange_rate),
                'account_id' => $template->account_id,
                'booked_on' => $date->toDateString(),
                'min_date' => $month->start()->toDateString(),
                'max_date' => $month->start()->endOfMonth()->toDateString(),
            ],
            'accounts' => array_values($accounts->map(fn (Account $account): array => [
                'id' => $account->id,
                'name' => $account->archived_at === null ? $account->name : $account->name.' (archived)',
                'icon' => $account->icon,
                'currency' => $account->currency,
                'balance' => $balances[$account->id],
                'archived' => $account->archived_at !== null,
            ])->all()),
            'needs_account' => $accounts->filter(
                fn (Account $account): bool => $account->archived_at === null,
            )->isEmpty(),
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     month: string,
     *     date: string,
     *     date_label: string,
     *     type: string,
     *     type_label: string,
     *     category: string|null,
     *     note: string|null,
     *     account: string,
     *     person: string|null,
     *     amount: array{
     *         amount: string,
     *         currency: string,
     *         formatted: string,
     *         original_amount: string|null,
     *         original_currency: string|null,
     *         original_formatted: string|null
     *     },
     *     status_label: string
     * }
     */
    private function present(Transaction $template, CalendarMonth $month, User $user): array
    {
        $date = $this->schedule->date($template, $month);
        $base = $this->converter->baseAmount((string) $template->amount, $template->currency, $user->workspace, $date);
        $account = $template->account;
        $name = $account->name;

        if ($account->archived_at !== null) {
            $name .= ' (archived)';
        }

        return [
            'id' => $template->id,
            'month' => $month->value(),
            'date' => $date->toDateString(),
            'date_label' => $date->format('j M Y'),
            'type' => $template->type->value,
            'type_label' => $template->typeLabel(),
            'category' => $template->category?->name,
            'note' => $template->note,
            'account' => $name,
            'person' => $template->person?->name,
            'amount' => $this->converter->present(
                (string) $template->amount,
                $template->currency,
                $base,
                $user->display_currency,
                $user->workspace,
                $date,
            )->toArray(),
            'status_label' => 'Pending',
        ];
    }
}
