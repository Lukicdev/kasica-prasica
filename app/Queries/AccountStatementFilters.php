<?php

namespace App\Queries;

use App\Enums\Currency;
use App\Enums\TransactionType;
use Illuminate\Http\Request;

/**
 * The account activity table's filters, sort, and page.
 *
 * Unlike the transaction table there is no month: an account statement
 * covers the whole history. The default order is oldest first, so the
 * running balance reads top to bottom.
 */
final class AccountStatementFilters
{
    /**
     * @param  'date'|'type'|'category'|'person'|'amount'  $sort
     * @param  'asc'|'desc'  $direction
     */
    public function __construct(
        public ?TransactionType $type = null,
        public ?int $categoryId = null,
        public ?int $personId = null,
        public ?string $currency = null,
        public string $search = '',
        public string $sort = 'date',
        public string $direction = 'asc',
        public int $page = 1,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $sort = match ($request->string('sort')->toString()) {
            'type' => 'type',
            'category' => 'category',
            'person' => 'person',
            'amount' => 'amount',
            default => 'date',
        };

        $direction = match ($request->string('direction')->toString()) {
            'asc' => 'asc',
            'desc' => 'desc',
            default => $sort === 'amount' ? 'desc' : 'asc',
        };

        $search = trim($request->string('search')->toString());

        if (mb_strlen($search) > 100) {
            $search = mb_substr($search, 0, 100);
        }

        $page = $request->integer('page');

        return new self(
            type: TransactionType::tryFrom($request->string('type')->toString()),
            categoryId: self::optionalId($request, 'category'),
            personId: self::optionalId($request, 'person'),
            currency: Currency::tryFrom($code = strtoupper($request->string('currency')->toString())) instanceof Currency ? $code : null,
            search: $search,
            sort: $sort,
            direction: $direction,
            page: $page > 0 ? $page : 1,
        );
    }

    private static function optionalId(Request $request, string $key): ?int
    {
        if (! $request->filled($key)) {
            return null;
        }

        $id = $request->integer($key);

        return $id > 0 ? $id : null;
    }
}
