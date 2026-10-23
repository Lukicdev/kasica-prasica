<?php

namespace App\Catalogs;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use App\Money\AccountBalance;
use App\Money\Decimal;
use App\Money\MoneyConverter;
use App\Money\MoneyFormatter;
use App\Money\Rate;
use App\Queries\TransactionFilters;
use App\Support\CalendarMonth;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The transaction table, the entry form, and the confirmation page.
 */
class TransactionCatalog
{
    public const int PER_PAGE = 25;

    /**
     * @var list<string>
     */
    private const array LIST_COLUMNS = [
        'id',
        'account_id',
        'destination_account_id',
        'category_id',
        'person_id',
        'type',
        'amount',
        'currency',
        'base_amount',
        'booked_on',
        'note',
        'repeats',
    ];

    public function __construct(
        private AccountBalance $balances,
        private MoneyConverter $converter,
        private MoneyFormatter $formatter,
        private RecurrenceCatalog $recurrences,
    ) {}

    /**
     * @return array{
     *     month: array{value: string, label: string, previous: string, next: string},
     *     filters: array{type: string|null, category: int|null, account: int|null, person: int|null, currency: string|null, search: string},
     *     sort: array{column: string, direction: string},
     *     filtered: bool,
     *     options: array{
     *         types: list<array{value: string, label: string}>,
     *         categories: list<array{id: int, name: string, kind: string, icon: string|null, color: string|null}>,
     *         accounts: list<array{id: int, name: string, icon: string|null}>,
     *         people: list<array{id: int, name: string}>,
     *         currencies: list<array{value: string, label: string}>
     *     },
     *     transactions: list<array{
     *         id: int,
     *         date: string,
     *         date_label: string,
     *         type: string,
     *         type_label: string,
     *         category: string|null,
     *         category_icon: string|null,
     *         category_color: string|null,
     *         note: string|null,
     *         account: string,
     *         account_icon: string|null,
     *         destination: string|null,
     *         destination_icon: string|null,
     *         person: string|null,
     *         amount: array{
     *             amount: string,
     *             currency: string,
     *             formatted: string,
     *             original_amount: string|null,
     *             original_currency: string|null,
     *             original_formatted: string|null
     *         },
     *         repeats: bool
     *     }>,
     *     pagination: array{current_page: int, last_page: int, per_page: int, total: int, from: int|null, to: int|null},
     *     total: array{amount: string, currency: string, formatted: string, negative: bool, caption: string},
     *     pending: list<array{
     *         id: int,
     *         month: string,
     *         date: string,
     *         date_label: string,
     *         type: string,
     *         type_label: string,
     *         category: string|null,
     *         note: string|null,
     *         account: string,
     *         person: string|null,
     *         amount: array{
     *             amount: string,
     *             currency: string,
     *             formatted: string,
     *             original_amount: string|null,
     *             original_currency: string|null,
     *             original_formatted: string|null
     *         },
     *         status_label: string
     *     }>
     * }
     */
    public function list(User $user, TransactionFilters $filters): array
    {
        $workspace = $user->workspace;
        $start = $filters->start();
        $nextMonth = $start->copy()->addMonth();

        $categories = Category::query()
            ->whereBelongsTo($workspace)
            ->ordered()
            ->get();
        $accounts = Account::query()
            ->whereBelongsTo($workspace)
            ->orderBy('name')
            ->get();
        $people = User::query()
            ->whereBelongsTo($workspace)
            ->orderBy('name')
            ->get(['id', 'name']);

        $categoryId = $this->knownId($filters->categoryId, $categories->modelKeys());
        $accountId = $this->knownId($filters->accountId, $accounts->modelKeys());
        $personId = $this->knownId($filters->personId, $people->modelKeys());

        $query = Transaction::query()
            ->whereBelongsTo($workspace)
            ->where('booked_on', '>=', $start->toDateString())
            ->where('booked_on', '<', $nextMonth->toDateString())
            ->with([
                'category:id,name,icon,color',
                'account:id,name,icon',
                'destinationAccount:id,name,icon',
                'person:id,name',
            ]);

        if ($filters->type !== null) {
            $query->where('type', $filters->type->value);
        }

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        if ($accountId !== null) {
            $query->where(function (Builder $query) use ($accountId): void {
                $query->where('account_id', $accountId)
                    ->orWhere('destination_account_id', $accountId);
            });
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

        $this->applySort($query, $filters->sort, $filters->direction);

        $summary = (clone $query)
            ->setEagerLoads([])
            ->reorder()
            ->get(['type', 'base_amount']);
        $page = $this->page($query, $filters->page);
        $display = $user->display_currency;

        return [
            'month' => [
                'value' => $filters->month,
                'label' => $start->format('F Y'),
                'previous' => $start->copy()->subMonth()->format('Y-m'),
                'next' => $start->copy()->addMonth()->format('Y-m'),
            ],
            'filters' => [
                'type' => $filters->type?->value,
                'category' => $categoryId,
                'account' => $accountId,
                'person' => $personId,
                'currency' => $filters->currency,
                'search' => $filters->search,
            ],
            'sort' => [
                'column' => $filters->sort,
                'direction' => $filters->direction,
            ],
            'filtered' => $filters->type !== null
                || $categoryId !== null
                || $accountId !== null
                || $personId !== null
                || $filters->currency !== null
                || $filters->search !== '',
            'options' => [
                'types' => TransactionType::options(),
                'categories' => array_values($categories->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->archived_at === null
                        ? $category->name
                        : $category->name.' (archived)',
                    'kind' => $category->kind->value,
                    'icon' => $category->icon,
                    'color' => $category->color,
                ])->all()),
                'accounts' => array_values($accounts->map(fn (Account $account): array => [
                    'id' => $account->id,
                    'name' => $account->archived_at === null
                        ? $account->name
                        : $account->name.' (archived)',
                    'icon' => $account->icon,
                ])->all()),
                'people' => array_values($people->map(fn (User $person): array => [
                    'id' => $person->id,
                    'name' => $person->name,
                ])->all()),
                'currencies' => array_values($user->workspace->currencies()
                    ->orderBy('code')
                    ->pluck('code')
                    ->map(fn (string $code): array => [
                        'value' => $code,
                        'label' => $code,
                    ])
                    ->all()),
            ],
            'transactions' => array_values($page->getCollection()->map(function (Transaction $transaction) use ($display, $user): array {
                $destination = $transaction->type === TransactionType::Transfer
                    ? $transaction->destinationAccount
                    : null;

                return [
                    'id' => $transaction->id,
                    'date' => $transaction->booked_on->toDateString(),
                    'date_label' => $transaction->booked_on->format('j M Y'),
                    'type' => $transaction->type->value,
                    'type_label' => $transaction->typeLabel(),
                    'category' => $transaction->category?->name,
                    'category_icon' => $transaction->category?->icon,
                    'category_color' => $transaction->category?->color,
                    'note' => $transaction->note,
                    'account' => $transaction->account->name,
                    'account_icon' => $transaction->account->icon,
                    'destination' => $destination?->name,
                    'destination_icon' => $destination?->icon,
                    'person' => $transaction->person?->name,
                    'amount' => $this->converter->present(
                        (string) $transaction->amount,
                        $transaction->currency,
                        (string) $transaction->base_amount,
                        $display,
                        $user->workspace,
                        $transaction->booked_on,
                    )->toArray(),
                    'repeats' => $transaction->repeats,
                ];
            })->all()),
            'pagination' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'from' => $page->firstItem(),
                'to' => $page->lastItem(),
            ],
            'total' => $this->total($summary, $display, $user->workspace, $start),
            'pending' => $this->recurrences->pending(
                $user,
                CalendarMonth::fromValue($filters->month),
                $filters->type,
                $categoryId,
                $accountId,
                $personId,
                $filters->currency,
                $filters->search,
            ),
        ];
    }

    /**
     * @param  array{type?: string, account?: int, category?: int, date?: string}  $prefill
     * @return array{
     *     transaction: array{
     *         id: int,
     *         type: string,
     *         amount: string,
     *         refund: bool,
     *         currency: string,
     *         account_id: int,
     *         account_amount: string,
     *         category_id: int|null,
     *         booked_on: string,
     *         person_id: int|null,
     *         note: string|null,
     *         recurring: bool
     *     }|null,
     *     accounts: list<array{id: int, name: string, icon: string|null, currency: string, balance: string, archived: bool}>,
     *     categories: list<array{id: int, name: string, kind: string, icon: string|null, color: string|null, label: string, archived: bool}>,
     *     people: list<array{id: int, name: string}>,
     *     defaults: array{type: string, account_id: int|null, category_id: int|null, person_id: int, date: string, currency: string},
     *     needs_account: bool
     * }
     */
    public function form(User $user, ?Transaction $transaction = null, array $prefill = []): array
    {
        $workspace = $user->workspace;
        $transaction?->loadMissing(['account']);

        $accounts = Account::query()
            ->whereBelongsTo($workspace)
            ->orderBy('name')
            ->get();

        $formAccounts = $accounts
            ->filter(function (Account $account) use ($transaction): bool {
                if ($transaction !== null && $account->id === $transaction->account_id) {
                    return true;
                }

                return $account->archived_at === null;
            })
            ->values();

        $balances = $this->balances->forAccounts($formAccounts);

        $categories = Category::query()
            ->whereBelongsTo($workspace)
            ->ordered()
            ->get()
            ->filter(function (Category $category) use ($transaction): bool {
                if ($transaction !== null && $category->id === $transaction->category_id) {
                    return true;
                }

                return $category->archived_at === null;
            })
            ->sortBy(fn (Category $category): array => [
                $category->kind->order(),
                $category->position,
                $category->id,
            ])
            ->values();

        $people = User::query()
            ->whereBelongsTo($workspace)
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectableAccounts = $formAccounts->filter(
            fn (Account $account): bool => $account->archived_at === null,
        );

        return [
            'transaction' => $transaction === null ? null : $this->formValues($transaction),
            'accounts' => array_values($formAccounts->map(fn (Account $account): array => [
                'id' => $account->id,
                'name' => $account->archived_at === null ? $account->name : $account->name.' (archived)',
                'icon' => $account->icon,
                'currency' => $account->currency,
                'balance' => $balances[$account->id],
                'archived' => $account->archived_at !== null,
            ])->all()),
            'categories' => array_values($categories->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'kind' => $category->kind->value,
                'icon' => $category->icon,
                'color' => $category->color,
                'label' => $category->archived_at === null
                    ? $category->choiceLabel()
                    : $category->choiceLabel().' (archived)',
                'archived' => $category->archived_at !== null,
            ])->all()),
            'people' => array_values($people->map(fn (User $person): array => [
                'id' => $person->id,
                'name' => $person->name,
            ])->all()),
            'defaults' => $this->defaults($user, $selectableAccounts, $categories, $prefill),
            'needs_account' => $transaction === null && $selectableAccounts->isEmpty(),
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     type: string,
     *     type_label: string,
     *     refund: bool,
     *     date: string,
     *     date_label: string,
     *     category: string|null,
     *     note: string|null,
     *     account: array{id: int, name: string, icon: string|null},
     *     destination: array{id: int, name: string, icon: string|null}|null,
     *     destination_amount: array{amount: string, currency: string, formatted: string, negative: bool}|null,
     *     destination_balance: array{amount: string, currency: string, formatted: string, negative: bool}|null,
     *     excluded_from_budget: bool,
     *     person: string|null,
     *     amount: array{
     *         amount: string,
     *         currency: string,
     *         formatted: string,
     *         original_amount: string|null,
     *         original_currency: string|null,
     *         original_formatted: string|null
     *     },
     *     account_amount: array{amount: string, currency: string, formatted: string, negative: bool},
     *     balance: array{amount: string, currency: string, formatted: string, negative: bool},
     *     updated_by: string|null,
     *     repeats: bool,
     *     from_recurrence: bool
     * }
     */
    public function detail(Transaction $transaction, User $user): array
    {
        $transaction->loadMissing([
            'account',
            'destinationAccount',
            'category',
            'person:id,name',
            'editor:id,name',
        ]);

        $editor = $transaction->editor;
        $updatedBy = null;

        if (
            $editor !== null
            && $transaction->updated_at !== null
            && $transaction->created_at !== null
            && $transaction->updated_at->greaterThan($transaction->created_at)
        ) {
            $updatedBy = $editor->name;
        }

        $account = $transaction->account;
        $destination = $transaction->destinationAccount;
        $balance = $this->balances->of($account);

        return [
            'id' => $transaction->id,
            'type' => $transaction->type->value,
            'type_label' => $transaction->typeLabel(),
            'refund' => $transaction->isRefund(),
            'date' => $transaction->booked_on->toDateString(),
            'date_label' => $transaction->booked_on->format('j M Y'),
            'category' => $transaction->category?->choiceLabel(),
            'note' => $transaction->note,
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'icon' => $account->icon,
            ],
            'destination' => $destination === null ? null : [
                'id' => $destination->id,
                'name' => $destination->name,
                'icon' => $destination->icon,
            ],
            'destination_amount' => $destination === null || $transaction->destination_amount === null
                ? null
                : $this->money((string) $transaction->destination_amount, $destination->currency),
            'destination_balance' => $destination === null
                ? null
                : $this->money($this->balances->of($destination), $destination->currency),
            'excluded_from_budget' => ! $transaction->type->isIncomeOrExpense(),
            'person' => $transaction->person?->name,
            'amount' => $this->converter->present(
                (string) $transaction->amount,
                $transaction->currency,
                (string) $transaction->base_amount,
                $user->display_currency,
                $user->workspace,
                $transaction->booked_on,
            )->toArray(),
            'account_amount' => $this->money((string) $transaction->account_amount, $account->currency),
            'balance' => $this->money($balance, $account->currency),
            'updated_by' => $updatedBy,
            'repeats' => $transaction->repeats,
            'from_recurrence' => $transaction->recurrence_id !== null,
        ];
    }

    /**
     * @param  Builder<Transaction>  $query
     * @return LengthAwarePaginator<int, Transaction>
     */
    private function page(Builder $query, int $page): LengthAwarePaginator
    {
        $paginator = (clone $query)->paginate(self::PER_PAGE, self::LIST_COLUMNS, 'page', $page);

        if ($page <= $paginator->lastPage()) {
            return $paginator;
        }

        return (clone $query)->paginate(self::PER_PAGE, self::LIST_COLUMNS, 'page', $paginator->lastPage());
    }

    /**
     * @param  Builder<Transaction>  $query
     * @param  'date'|'type'|'category'|'note'|'account'|'person'|'amount'  $sort
     * @param  'asc'|'desc'  $direction
     */
    private function applySort(Builder $query, string $sort, string $direction): void
    {
        match ($sort) {
            'type' => $query->orderBy('type', $direction)->orderBy('id'),
            'category' => $query->orderBy(
                Category::query()->select('name')->whereColumn('categories.id', 'transactions.category_id'),
                $direction,
            )->orderBy('id'),
            'note' => $query->orderBy('note', $direction)->orderBy('id'),
            'account' => $query->orderBy(
                Account::query()->select('name')->whereColumn('accounts.id', 'transactions.account_id'),
                $direction,
            )->orderBy('id'),
            'person' => $query->orderBy(
                User::query()->select('name')->whereColumn('users.id', 'transactions.person_id'),
                $direction,
            )->orderBy('id'),
            'amount' => $query->orderBy('base_amount', $direction)->orderBy('id'),
            default => $query->orderBy('booked_on', $direction)->orderBy('id', $direction),
        };
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @return array{amount: string, currency: string, formatted: string, negative: bool, caption: string}
     */
    private function total(Collection $transactions, string $display, Workspace $workspace, CarbonInterface $on): array
    {
        $net = Decimal::of('0');

        foreach ($transactions as $transaction) {
            if (! $transaction->type->isIncomeOrExpense()) {
                continue;
            }

            $base = Decimal::of((string) $transaction->base_amount);
            $net = $transaction->type === TransactionType::Income
                ? $net->add($base)
                : $net->subtract($base);
        }

        $amount = $this->converter->displayAmount($net->toString(MoneyConverter::SCALE), $display, $workspace, $on);

        return [
            'amount' => $amount,
            'currency' => $display,
            'formatted' => $this->formatter->format($amount, $display),
            'negative' => Decimal::of($amount)->isNegative(),
            'caption' => 'Income minus spending',
        ];
    }

    /**
     * @param  list<int>|array<int, int|string>  $ids
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
     *     id: int,
     *     type: string,
     *     amount: string,
     *     refund: bool,
     *     currency: string,
     *     exchange_rate: string,
     *     account_id: int,
     *     account_amount: string,
     *     category_id: int|null,
     *     booked_on: string,
     *     person_id: int|null,
     *     note: string|null,
     *     recurring: bool
     * }
     */
    private function formValues(Transaction $transaction): array
    {
        $amount = Decimal::of((string) $transaction->amount);
        $absolute = $amount->isNegative()
            ? Decimal::of('0')->subtract($amount)->toString(MoneyConverter::SCALE)
            : $amount->toString(MoneyConverter::SCALE);

        return [
            'id' => $transaction->id,
            'type' => $transaction->type->value,
            'amount' => $absolute,
            'refund' => $transaction->isRefund(),
            'currency' => $transaction->currency,
            'exchange_rate' => Rate::trim((string) $transaction->exchange_rate),
            'account_id' => $transaction->account_id,
            'account_amount' => Decimal::of((string) $transaction->account_amount)->toString(MoneyConverter::SCALE),
            'category_id' => $transaction->category_id,
            'booked_on' => $transaction->booked_on->toDateString(),
            'person_id' => $transaction->person_id,
            'note' => $transaction->note,
            'recurring' => $transaction->repeats,
        ];
    }

    /**
     * @param  Collection<int, Account>  $accounts
     * @param  Collection<int, Category>  $categories
     * @param  array{type?: string, account?: int, category?: int, date?: string}  $prefill
     * @return array{type: string, account_id: int|null, category_id: int|null, person_id: int, date: string, currency: string}
     */
    private function defaults(User $user, Collection $accounts, Collection $categories, array $prefill): array
    {
        $type = TransactionType::tryFrom($prefill['type'] ?? '');

        if (! $type instanceof TransactionType || ! $type->isIncomeOrExpense()) {
            $type = TransactionType::Expense;
        }
        $account = $this->prefilledAccount($user, $accounts, $prefill['account'] ?? null);
        $category = $this->prefilledCategory($categories, $prefill['category'] ?? null);

        if ($category !== null && $category->kind->value !== $type->value) {
            $type = TransactionType::from($category->kind->value);
        }

        return [
            'type' => $type->value,
            'account_id' => $account?->id,
            'category_id' => $category?->id,
            'person_id' => $user->id,
            'date' => $this->prefilledDate($prefill['date'] ?? null),
            'currency' => $account instanceof Account ? $account->currency : $user->workspace->base_currency,
        ];
    }

    /**
     * @param  Collection<int, Account>  $accounts
     */
    private function prefilledAccount(User $user, Collection $accounts, ?int $requested): ?Account
    {
        if ($requested !== null) {
            $chosen = $accounts->firstWhere('id', $requested);

            if ($chosen instanceof Account) {
                return $chosen;
            }
        }

        $lastId = Transaction::query()
            ->whereBelongsTo($user->workspace)
            ->where('created_by', $user->id)
            ->latest('id')
            ->value('account_id');

        $last = $accounts->firstWhere('id', $lastId);

        if ($last instanceof Account) {
            return $last;
        }

        $first = $accounts->first();

        return $first instanceof Account ? $first : null;
    }

    /**
     * @param  Collection<int, Category>  $categories
     */
    private function prefilledCategory(Collection $categories, ?int $requested): ?Category
    {
        if ($requested === null) {
            return null;
        }

        $category = $categories->firstWhere('id', $requested);

        if (! $category instanceof Category || $category->archived_at !== null) {
            return null;
        }

        return $category;
    }

    private function prefilledDate(?string $date): string
    {
        if ($date === null || preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            return now()->toDateString();
        }

        $parsed = Carbon::createFromFormat('!Y-m-d', $date);

        if (! $parsed instanceof CarbonInterface || $parsed->format('Y-m-d') !== $date) {
            return now()->toDateString();
        }

        return $date;
    }

    /**
     * @return array{amount: string, currency: string, formatted: string, negative: bool}
     */
    private function money(string $amount, string $currency): array
    {
        $normalized = Decimal::of($amount)->toString(MoneyConverter::SCALE);

        return [
            'amount' => $normalized,
            'currency' => $currency,
            'formatted' => $this->formatter->format($normalized, $currency),
            'negative' => Decimal::of($normalized)->isNegative(),
        ];
    }
}
