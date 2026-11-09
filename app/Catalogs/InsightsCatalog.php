<?php

namespace App\Catalogs;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use App\Money\Decimal;
use App\Money\MoneyConverter;
use App\Support\CalendarMonth;

/**
 * Income against spending for the last twelve months.
 *
 * Amounts are the sum of income and expense transactions in the base currency.
 */
class InsightsCatalog
{
    private const int TREND_MONTHS = 12;

    public function __construct(
        private MoneyConverter $converter,
    ) {}

    /**
     * @return array{trends: array<string, mixed>}
     */
    public function page(User $user): array
    {
        return [
            'trends' => $this->trends($user->workspace, $user->display_currency),
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
            $withYear = $index === 0 || $month->start()->month === 1;

            $rows[] = [
                'value' => $month->value(),
                'label' => $month->label(),
                'short_label' => $month->start()->format($withYear ? 'M Y' : 'M'),
                'income' => $this->figure($bucket['income']->toString(MoneyConverter::SCALE), $display, $workspace),
                'spent' => $this->figure($bucket['spent']->toString(MoneyConverter::SCALE), $display, $workspace),
            ];
        }

        return [
            'through' => $this->month($through),
            'months' => $rows,
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
