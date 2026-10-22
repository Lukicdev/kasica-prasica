<?php

namespace App\Support;

use App\Enums\Currency;
use App\Enums\TransactionType;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * The transaction table's month, filters, sort, and page.
 *
 * A month is a calendar month. The workspace does not store a start day yet.
 */
final class TransactionFilters
{
    /**
     * @param  'date'|'type'|'category'|'note'|'account'|'person'|'amount'  $sort
     * @param  'asc'|'desc'  $direction
     */
    public function __construct(
        public string $month,
        public ?TransactionType $type,
        public ?int $categoryId,
        public ?int $accountId,
        public ?int $personId,
        public ?string $currency,
        public string $search,
        public string $sort,
        public string $direction,
        public int $page,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $month = $request->string('month')->toString();

        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) !== 1) {
            $month = now()->format('Y-m');
        }

        $sort = match ($request->string('sort')->toString()) {
            'type' => 'type',
            'category' => 'category',
            'note' => 'note',
            'account' => 'account',
            'person' => 'person',
            'amount' => 'amount',
            default => 'date',
        };

        $direction = match ($request->string('direction')->toString()) {
            'asc' => 'asc',
            'desc' => 'desc',
            default => $sort === 'date' || $sort === 'amount' ? 'desc' : 'asc',
        };

        $search = trim($request->string('search')->toString());

        if (mb_strlen($search) > 100) {
            $search = mb_substr($search, 0, 100);
        }

        $page = $request->integer('page');

        return new self(
            month: $month,
            type: TransactionType::tryFrom($request->string('type')->toString()),
            categoryId: self::optionalId($request, 'category'),
            accountId: self::optionalId($request, 'account'),
            personId: self::optionalId($request, 'person'),
            currency: Currency::tryFrom($code = strtoupper($request->string('currency')->toString())) instanceof Currency ? $code : null,
            search: $search,
            sort: $sort,
            direction: $direction,
            page: $page > 0 ? $page : 1,
        );
    }

    public function start(): CarbonInterface
    {
        $start = Carbon::createFromFormat('!Y-m-d', $this->month.'-01');

        if (! $start instanceof CarbonInterface) {
            return now()->startOfMonth();
        }

        return $start->startOfMonth();
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
