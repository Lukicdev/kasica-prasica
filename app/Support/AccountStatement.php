<?php

namespace App\Support;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;

/**
 * Opening balance first, then every transaction from oldest to newest.
 * The running balance starts at the opening balance and applies each
 * transaction, including any dated before the opening balance.
 */
class AccountStatement
{
    public const int PER_PAGE = 25;

    /**
     * @var list<string>
     */
    private const array LINE_COLUMNS = [
        'id',
        'account_id',
        'destination_account_id',
        'category_id',
        'person_id',
        'type',
        'amount',
        'currency',
        'account_amount',
        'destination_amount',
        'booked_on',
        'note',
        'repeats',
    ];

    /**
     * @var list<string>
     */
    private const array BALANCE_COLUMNS = [
        'account_id',
        'destination_account_id',
        'type',
        'account_amount',
        'destination_amount',
    ];

    public function __construct(
        private AccountBalance $balances,
        private MoneyFormatter $formatter,
    ) {}

    /**
     * @return array{
     *     lines: list<array{
     *         id: int|null,
     *         kind: string,
     *         date: string,
     *         date_label: string,
     *         type: string|null,
     *         type_label: string,
     *         category: string|null,
     *         category_icon: string|null,
     *         note: string|null,
     *         person: string|null,
     *         repeats: bool,
     *         amount: array{
     *             amount: string,
     *             currency: string,
     *             formatted: string,
     *             negative: bool,
     *             original_formatted: string|null
     *         }|null,
     *         running_balance: array{amount: string, currency: string, formatted: string, negative: bool}
     *     }>,
     *     pagination: array{current_page: int, last_page: int, per_page: int, total: int, from: int|null, to: int|null}
     * }
     */
    public function lines(Account $account, int $page = 1): array
    {
        $query = $this->transactions($account);
        $total = (clone $query)->count();
        $lastPage = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max($page, 1), $lastPage);
        $offset = ($page - 1) * self::PER_PAGE;
        $running = Decimal::of((string) $account->opening_balance)->toString(MoneyConverter::SCALE);

        if ($offset > 0) {
            $earlier = (clone $query)->limit($offset)->get(self::BALANCE_COLUMNS);

            foreach ($earlier as $transaction) {
                $running = $this->balances->applyOnAccount($running, $transaction, $account->id);
            }
        }

        $transactions = (clone $query)
            ->with([
                'person:id,name',
                'account:id,name',
                'destinationAccount:id,name',
                'category:id,name,icon',
            ])
            ->skip($offset)
            ->take(self::PER_PAGE)
            ->get(self::LINE_COLUMNS);

        $lines = $page === 1 ? [$this->openingLine($account, $running)] : [];

        foreach ($transactions as $transaction) {
            $running = $this->balances->applyOnAccount($running, $transaction, $account->id);

            $lines[] = [
                'id' => $transaction->id,
                'kind' => 'transaction',
                'date' => $transaction->booked_on->toDateString(),
                'date_label' => $transaction->booked_on->format('j M Y'),
                'type' => $transaction->type->value,
                'type_label' => $this->typeLabel($transaction, $account),
                'category' => $transaction->category?->name,
                'category_icon' => $transaction->category?->icon,
                'note' => $transaction->note,
                'person' => $transaction->person?->name,
                'repeats' => $transaction->repeats,
                'amount' => $this->amount($transaction, $account),
                'running_balance' => $this->money($running, $account),
            ];
        }

        return [
            'lines' => $lines,
            'pagination' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => self::PER_PAGE,
                'total' => $total,
                'from' => $total === 0 ? null : $offset + 1,
                'to' => $total === 0 ? null : $offset + $transactions->count(),
            ],
        ];
    }

    /**
     * @return Builder<Transaction>
     */
    private function transactions(Account $account): Builder
    {
        return Transaction::query()
            ->where(function (Builder $query) use ($account): void {
                $query->where('account_id', $account->id)
                    ->orWhere('destination_account_id', $account->id);
            })
            ->orderBy('booked_on')
            ->orderBy('id');
    }

    /**
     * @return array{
     *     id: null,
     *     kind: string,
     *     date: string,
     *     date_label: string,
     *     type: null,
     *     type_label: string,
     *     category: null,
     *     category_icon: null,
     *     note: null,
     *     person: null,
     *     repeats: false,
     *     amount: null,
     *     running_balance: array{amount: string, currency: string, formatted: string, negative: bool}
     * }
     */
    private function openingLine(Account $account, string $running): array
    {
        return [
            'id' => null,
            'kind' => 'opening',
            'date' => $account->opening_balance_on->toDateString(),
            'date_label' => $account->opening_balance_on->format('j M Y'),
            'type' => null,
            'type_label' => 'Opening balance',
            'category' => null,
            'category_icon' => null,
            'note' => null,
            'person' => null,
            'repeats' => false,
            'amount' => null,
            'running_balance' => $this->money($running, $account),
        ];
    }

    private function typeLabel(Transaction $transaction, Account $account): string
    {
        if ($transaction->type !== TransactionType::Transfer) {
            return $transaction->typeLabel();
        }

        if ($transaction->destination_account_id === $account->id) {
            return 'Transfer from '.$transaction->account->name;
        }

        return 'Transfer to '.$transaction->destinationAccount->name;
    }

    /**
     * @return array{
     *     amount: string,
     *     currency: string,
     *     formatted: string,
     *     negative: bool,
     *     original_formatted: string|null
     * }
     */
    private function amount(Transaction $transaction, Account $account): array
    {
        $incoming = $transaction->type === TransactionType::Transfer
            && $transaction->destination_account_id === $account->id;
        $movement = $incoming
            ? (string) ($transaction->destination_amount ?? '0')
            : (string) $transaction->account_amount;
        $accountAmount = Decimal::of($movement)->toString(MoneyConverter::SCALE);
        $originalAmount = Decimal::of((string) $transaction->amount)->toString(MoneyConverter::SCALE);

        return [
            'amount' => $accountAmount,
            'currency' => $account->currency->value,
            'formatted' => $this->formatter->format($accountAmount, $account->currency),
            'negative' => Decimal::of($accountAmount)->isNegative(),
            'original_formatted' => $transaction->currency === $account->currency
                ? null
                : $this->formatter->format($originalAmount, $transaction->currency),
        ];
    }

    /**
     * @return array{amount: string, currency: string, formatted: string, negative: bool}
     */
    private function money(string $amount, Account $account): array
    {
        $normalized = Decimal::of($amount)->toString(MoneyConverter::SCALE);

        return [
            'amount' => $normalized,
            'currency' => $account->currency->value,
            'formatted' => $this->formatter->format($normalized, $account->currency),
            'negative' => Decimal::of($normalized)->isNegative(),
        ];
    }
}
