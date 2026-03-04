<?php

declare(strict_types=1);

namespace App\Actions\Transaction;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListTransactionsAction
{
    private const PER_PAGE = 20;

    /**
     * Valid sort columns.
     *
     * @var list<string>
     */
    private const SORT_COLUMNS = [
        'transaction_date',
        'description',
        'type',
        'amount',
        'account_name',
        'category_name',
    ];

    /**
     * Get paginated transactions for the user with sorting.
     *
     * @param  array{sort?: string, direction?: string, page?: int}  $filters
     * @return array{transactions: LengthAwarePaginator, sort: string, direction: string}
     */
    public function handle(User $user, array $filters): array
    {
        $sort = $this->validSort($filters['sort'] ?? 'transaction_date');
        $directionRaw = $filters['direction'] ?? 'desc';
        $direction = in_array($directionRaw, ['asc', 'desc'], true) ? $directionRaw : 'desc';

        $query = $user->transactions()
            ->with(['account', 'category']);

        if ($sort === 'account_name') {
            $query->join('accounts', 'transactions.account_id', '=', 'accounts.id')
                ->orderBy('accounts.name', $direction)
                ->select('transactions.*');
        } elseif ($sort === 'category_name') {
            $query->leftJoin('categories', 'transactions.category_id', '=', 'categories.id')
                ->orderByRaw('categories.name IS NULL')
                ->orderBy('categories.name', $direction)
                ->select('transactions.*');
        } else {
            $query->orderBy($sort, $direction);
        }

        $transactions = $query->paginate(self::PER_PAGE)->withQueryString();

        return [
            'transactions' => $transactions,
            'sort' => $sort,
            'direction' => $direction,
        ];
    }

    private function validSort(string $sort): string
    {
        return in_array($sort, self::SORT_COLUMNS, true) ? $sort : 'transaction_date';
    }
}
