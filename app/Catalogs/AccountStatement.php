<?php

namespace App\Catalogs;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Money\AccountBalance;
use App\Money\Decimal;
use App\Money\MoneyConverter;
use App\Money\MoneyFormatter;
use App\Queries\AccountStatementFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * An account's transactions, filtered and sorted like the transaction table.
 * Each line carries the balance right after that transaction in date order:
 * the opening balance plus every earlier transaction, including any dated
 * before the opening balance. The opening line shows only on an unfiltered,
 * date-ordered statement, at its oldest end.
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
        'id',
        'account_id',
        'destination_account_id',
        'type',
        'account_amount',
        'destination_amount',
    ];

    public function __construct(
        private AccountBalance $balances,
        private MoneyFormatter $formatter,
        private TransactionCatalog $catalog,
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
     *         category_color: string|null,
     *         note: string|null,
     *         counterparty: array{name: string, icon: string|null, direction: 'from'|'to'}|null,
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
     *     pagination: array{current_page: int, last_page: int, per_page: int, total: int, from: int|null, to: int|null},
     *     filters: array{type: string|null, category: int|null, person: int|null, currency: string|null, search: string},
     *     sort: array{column: string, direction: string},
     *     filtered: bool,
     *     options: array{
     *         types: list<array{value: string, label: string}>,
     *         categories: list<array{id: int, name: string, kind: string, icon: string|null, color: string|null}>,
     *         people: list<array{id: int, name: string}>,
     *         currencies: list<array{value: string, label: string}>
     *     }
     * }
     */
    public function lines(Account $account, ?AccountStatementFilters $filters = null): array
    {
        $filters ??= new AccountStatementFilters;
        $options = $this->catalog->filterOptions($account->workspace);
        $categoryId = $this->knownId($filters->categoryId, array_column($options['categories'], 'id'));
        $personId = $this->knownId($filters->personId, array_column($options['people'], 'id'));
        $filtered = $filters->type !== null
            || $categoryId !== null
            || $personId !== null
            || $filters->currency !== null
            || $filters->search !== '';

        $query = $this->transactions($account);

        if ($filters->type !== null) {
            $query->where('type', $filters->type->value);
        }

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        if ($personId !== null) {
            $query->where('person_id', $personId);
        }

        if ($filters->currency !== null) {
            $query->where('currency', $filters->currency);
        }

        if ($filters->search !== '') {
            $like = '%'.addcslashes($filters->search, '%_\\').'%';
            $query->whereRaw('note like ? escape ?', [$like, '\\']);
        }

        $this->applySort($query, $account, $filters->sort, $filters->direction);

        $total = (clone $query)->count();
        $lastPage = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max($filters->page, 1), $lastPage);
        $offset = ($page - 1) * self::PER_PAGE;

        $transactions = (clone $query)
            ->with([
                'person:id,name',
                'account:id,name,icon',
                'destinationAccount:id,name,icon',
                'category:id,name,icon,color',
            ])
            ->skip($offset)
            ->take(self::PER_PAGE)
            ->get(self::LINE_COLUMNS);

        $balances = $this->runningBalances($account, $transactions);
        $lines = [];

        foreach ($transactions as $transaction) {
            $lines[] = [
                'id' => $transaction->id,
                'kind' => 'transaction',
                'date' => $transaction->booked_on->toDateString(),
                'date_label' => $transaction->booked_on->format('j M Y'),
                'type' => $transaction->type->value,
                'type_label' => $this->typeLabel($transaction, $account),
                'category' => $transaction->category?->name,
                'category_icon' => $transaction->category?->icon,
                'category_color' => $transaction->category?->color,
                'note' => $transaction->note,
                'counterparty' => $this->counterparty($transaction, $account),
                'person' => $transaction->person?->name,
                'repeats' => $transaction->repeats,
                'amount' => $this->amount($transaction, $account),
                'running_balance' => $this->money($balances[$transaction->id], $account),
            ];
        }

        // The opening balance sits at the oldest end of an unfiltered, date-ordered statement.
        if (! $filtered && $filters->sort === 'date') {
            $opening = $this->openingLine($account, $this->openingBalance($account));

            if ($filters->direction === 'asc' && $page === 1) {
                array_unshift($lines, $opening);
            } elseif ($filters->direction === 'desc' && $page === $lastPage) {
                $lines[] = $opening;
            }
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
            'filters' => [
                'type' => $filters->type?->value,
                'category' => $categoryId,
                'person' => $personId,
                'currency' => $filters->currency,
                'search' => $filters->search,
            ],
            'sort' => [
                'column' => $filters->sort,
                'direction' => $filters->direction,
            ],
            'filtered' => $filtered,
            'options' => [
                'types' => $options['types'],
                'categories' => $options['categories'],
                'people' => $options['people'],
                'currencies' => $options['currencies'],
            ],
        ];
    }

    /**
     * The account balance right after each given transaction in date order,
     * whatever order or filters the table shows them in.
     *
     * @param  Collection<int, Transaction>  $transactions
     * @return array<int, string>
     */
    private function runningBalances(Account $account, Collection $transactions): array
    {
        if ($transactions->isEmpty()) {
            return [];
        }

        $wanted = array_flip($transactions->modelKeys());
        $latest = $transactions->max(fn (Transaction $transaction): string => (string) $transaction->getRawOriginal('booked_on'));
        $running = $this->openingBalance($account);
        $balances = [];

        $history = $this->transactions($account)
            ->where('booked_on', '<=', $latest)
            ->orderBy('booked_on')
            ->orderBy('id')
            ->get(self::BALANCE_COLUMNS);

        foreach ($history as $transaction) {
            $running = $this->balances->applyOnAccount($running, $transaction, $account->id);

            if (isset($wanted[$transaction->id])) {
                $balances[$transaction->id] = $running;
            }
        }

        return $balances;
    }

    private function openingBalance(Account $account): string
    {
        return Decimal::of((string) $account->opening_balance)->toString(MoneyConverter::SCALE);
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
            });
    }

    /**
     * @param  Builder<Transaction>  $query
     * @param  'date'|'type'|'category'|'person'|'amount'  $sort
     * @param  'asc'|'desc'  $direction
     */
    private function applySort(Builder $query, Account $account, string $sort, string $direction): void
    {
        match ($sort) {
            'type' => $query->orderBy('type', $direction)->orderBy('booked_on')->orderBy('id'),
            'category' => $query->orderBy(
                Category::query()->select('name')->whereColumn('categories.id', 'transactions.category_id'),
                $direction,
            )->orderBy('booked_on')->orderBy('id'),
            'person' => $query->orderBy(
                User::query()->select('name')->whereColumn('users.id', 'transactions.person_id'),
                $direction,
            )->orderBy('booked_on')->orderBy('id'),
            // The amount as it moved this account: an incoming transfer counts what arrived.
            'amount' => $query->orderByRaw(
                'case when destination_account_id = ? then destination_amount else account_amount end '.$direction,
                [$account->id],
            )->orderBy('booked_on')->orderBy('id'),
            default => $query->orderBy('booked_on', $direction)->orderBy('id', $direction),
        };
    }

    /**
     * @param  list<int|string>  $ids
     */
    private function knownId(?int $id, array $ids): ?int
    {
        if ($id === null) {
            return null;
        }

        return in_array($id, array_map(intval(...), $ids), true) ? $id : null;
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
     *     category_color: null,
     *     note: null,
     *     counterparty: null,
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
            'category_color' => null,
            'note' => null,
            'counterparty' => null,
            'person' => null,
            'repeats' => false,
            'amount' => null,
            'running_balance' => $this->money($running, $account),
        ];
    }

    /**
     * The other account in a transfer, named from this account's point of view.
     *
     * @return array{name: string, icon: string|null, direction: 'from'|'to'}|null
     */
    private function counterparty(Transaction $transaction, Account $account): ?array
    {
        if ($transaction->type !== TransactionType::Transfer) {
            return null;
        }

        if ($transaction->destination_account_id === $account->id) {
            return [
                'name' => $transaction->account->name,
                'icon' => $transaction->account->icon,
                'direction' => 'from',
            ];
        }

        if ($transaction->destinationAccount === null) {
            return null;
        }

        return [
            'name' => $transaction->destinationAccount->name,
            'icon' => $transaction->destinationAccount->icon,
            'direction' => 'to',
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
            'currency' => $account->currency,
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
            'currency' => $account->currency,
            'formatted' => $this->formatter->format($normalized, $account->currency),
            'negative' => Decimal::of($normalized)->isNegative(),
        ];
    }
}
