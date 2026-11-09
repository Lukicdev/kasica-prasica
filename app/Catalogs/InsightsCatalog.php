<?php

namespace App\Catalogs;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Asset;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use App\Money\AccountBalance;
use App\Money\Decimal;
use App\Money\MoneyConverter;
use App\Support\CalendarMonth;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Income against spending, and net worth, for the last twelve months.
 *
 * Income and spending are the sums of those transactions in the base currency.
 * Net worth is account balances plus asset values on each date. Debt is not
 * subtracted yet.
 */
class InsightsCatalog
{
    private const int TREND_MONTHS = 12;

    public function __construct(
        private MoneyConverter $converter,
        private AccountBalance $balances,
    ) {}

    /**
     * @return array{
     *     trends: array<string, mixed>,
     *     net_worth: array<string, mixed>
     * }
     */
    public function page(User $user): array
    {
        $workspace = $user->workspace;
        $display = $user->display_currency;

        return [
            'trends' => $this->trends($workspace, $display),
            'net_worth' => $this->netWorth($workspace, $display),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function trends(Workspace $workspace, string $display): array
    {
        $through = CalendarMonth::current();
        $months = $this->trendMonths($through);
        $first = $months[0];
        $last = $months[array_key_last($months)];
        $transactions = Transaction::query()
            ->whereBelongsTo($workspace)
            ->whereIn('type', [TransactionType::Income->value, TransactionType::Expense->value])
            ->where('booked_on', '>=', $first->start()->toDateString())
            ->where('booked_on', '<', $last->endExclusive()->toDateString())
            ->get(['id', 'type', 'base_amount', 'booked_on']);

        /** @var array<string, array{income: Decimal, spent: Decimal}> $buckets */
        $buckets = [];

        foreach ($months as $month) {
            $buckets[$month->value()] = [
                'income' => Decimal::of('0'),
                'spent' => Decimal::of('0'),
            ];
        }

        foreach ($transactions as $transaction) {
            $key = $transaction->booked_on->format('Y-m');

            if (! array_key_exists($key, $buckets)) {
                continue;
            }

            $amount = Decimal::of((string) $transaction->base_amount);
            $field = $transaction->type === TransactionType::Income ? 'income' : 'spent';
            $buckets[$key][$field] = $buckets[$key][$field]->add($amount);
        }

        $rows = [];

        foreach ($months as $index => $month) {
            $bucket = $buckets[$month->value()];

            $rows[] = $this->monthRow($month, $index, [
                'income' => $this->figure($bucket['income']->toString(MoneyConverter::SCALE), $display, $workspace),
                'spent' => $this->figure($bucket['spent']->toString(MoneyConverter::SCALE), $display, $workspace),
            ]);
        }

        return [
            'through' => $this->month($through),
            'months' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function netWorth(Workspace $workspace, string $display): array
    {
        $through = CalendarMonth::current();
        $months = $this->trendMonths($through);
        $dates = [];

        foreach ($months as $month) {
            $dates[$month->value()] = $this->asOf($month, $through);
        }

        $comparison = $this->comparisonDate($through);
        $accounts = Account::query()
            ->whereBelongsTo($workspace)
            ->get(['id', 'currency', 'opening_balance', 'opening_balance_on']);
        $assets = Asset::query()
            ->whereBelongsTo($workspace)
            ->get(['id', 'currency', 'value', 'valued_on']);
        $snapshotDates = array_values($dates);

        if (! in_array($comparison['date'], $snapshotDates, true)) {
            $snapshotDates[] = $comparison['date'];
        }

        $balances = $this->balances->forAccountsThrough($accounts, $snapshotDates);
        $assetBases = $this->assetBases($assets, $workspace);
        $rows = [];

        foreach ($months as $index => $month) {
            $rows[] = $this->monthRow($month, $index, [
                'net_worth' => $this->figure(
                    $this->worthBase($accounts, $assetBases, $balances, $dates[$month->value()], $workspace),
                    $display,
                    $workspace,
                ),
            ]);
        }

        $currentBase = $this->worthBase($accounts, $assetBases, $balances, $dates[$through->value()], $workspace);
        $previousBase = $this->worthBase($accounts, $assetBases, $balances, $comparison['date'], $workspace);

        return [
            'through' => $this->month($through),
            'months' => $rows,
            'previous' => [
                'month' => $comparison['month']->label(),
                'day' => $comparison['day'],
                'balance' => $this->figure($previousBase, $display, $workspace),
                'percent' => $this->changePercent($currentBase, $previousBase),
            ],
        ];
    }

    /**
     * Same day number last month, or that month's last day when it is shorter.
     *
     * @return array{month: CalendarMonth, day: int, date: string}
     */
    private function comparisonDate(CalendarMonth $through): array
    {
        $previous = $through->previous();
        $day = min(CarbonImmutable::now()->day, $previous->start()->daysInMonth);

        return [
            'month' => $previous,
            'day' => $day,
            'date' => $previous->start()->addDays($day - 1)->toDateString(),
        ];
    }

    /**
     * @param  Collection<int, Account>  $accounts
     * @param  list<array{valued_on: string, base: string}>  $assets
     * @param  array<string, array<int, string>>  $balances
     */
    private function worthBase(Collection $accounts, array $assets, array $balances, string $date, Workspace $workspace): string
    {
        $total = Decimal::of('0');

        foreach ($accounts as $account) {
            $total = $total->add(Decimal::of($this->inBase(
                $balances[$date][$account->id],
                $account->currency,
                $workspace,
            )));
        }

        foreach ($assets as $asset) {
            if ($asset['valued_on'] > $date) {
                continue;
            }

            $total = $total->add(Decimal::of($asset['base']));
        }

        return $total->toString(MoneyConverter::SCALE);
    }

    /**
     * How far the current total has moved from the earlier one, as a whole percent.
     */
    private function changePercent(string $current, string $previous): ?int
    {
        $baseline = Decimal::of($previous);

        if ($baseline->isZero()) {
            return null;
        }

        if ($baseline->isNegative()) {
            $baseline = Decimal::of('0')->subtract($baseline);
        }

        return (int) Decimal::of($current)
            ->subtract(Decimal::of($previous))
            ->multiply(Decimal::of('100'))
            ->divide($baseline, 0)
            ->toString(0);
    }

    /**
     * @param  Collection<int, Asset>  $assets
     * @return list<array{valued_on: string, base: string}>
     */
    private function assetBases(Collection $assets, Workspace $workspace): array
    {
        $rows = [];

        foreach ($assets as $asset) {
            $value = Decimal::of((string) $asset->value)->toString(MoneyConverter::SCALE);

            $rows[] = [
                'valued_on' => $asset->valued_on->toDateString(),
                'base' => $this->inBase($value, $asset->currency, $workspace),
            ];
        }

        return $rows;
    }

    private function inBase(string $amount, string $currency, Workspace $workspace): string
    {
        if ($currency === $workspace->base_currency) {
            return Decimal::of($amount)->toString(MoneyConverter::SCALE);
        }

        return $this->converter->baseAmount($amount, $currency, $workspace);
    }

    /**
     * The current month stops today. Earlier months use their last day.
     */
    private function asOf(CalendarMonth $month, CalendarMonth $through): string
    {
        if ($month->value() === $through->value()) {
            return CarbonImmutable::now()->toDateString();
        }

        return $month->endExclusive()->subDay()->toDateString();
    }

    /**
     * @param  array<string, mixed>  $figures
     * @return array<string, mixed>
     */
    private function monthRow(CalendarMonth $month, int $index, array $figures): array
    {
        $withYear = $index === 0 || $month->start()->month === 1;

        return [
            'value' => $month->value(),
            'label' => $month->label(),
            'short_label' => $month->start()->format($withYear ? 'M Y' : 'M'),
            ...$figures,
        ];
    }

    /**
     * @return non-empty-list<CalendarMonth>
     */
    private function trendMonths(CalendarMonth $through): array
    {
        $months = [$through];
        $cursor = $through;

        for ($index = 1; $index < self::TREND_MONTHS; $index++) {
            $cursor = $cursor->previous();
            array_unshift($months, $cursor);
        }

        return $months;
    }

    /**
     * @return array{value: string, label: string}
     */
    private function month(CalendarMonth $month): array
    {
        return [
            'value' => $month->value(),
            'label' => $month->label(),
        ];
    }

    /**
     * @return array{
     *     amount: string,
     *     currency: string,
     *     formatted: string,
     *     original_amount: string|null,
     *     original_currency: string|null,
     *     original_formatted: string|null,
     *     negative: bool,
     *     base_amount: string
     * }
     */
    private function figure(string $baseAmount, string $display, Workspace $workspace): array
    {
        $normalized = Decimal::of($baseAmount)->toString(MoneyConverter::SCALE);
        $presented = $this->converter->present($normalized, $workspace->base_currency, $normalized, $display, $workspace);

        return [
            ...$presented->toArray(),
            'negative' => Decimal::of($presented->amount)->isNegative(),
            'base_amount' => $normalized,
        ];
    }
}
