<?php

namespace App\Support;

use App\Enums\CategoryKind;
use App\Enums\Currency;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Month-over-month spending, and income against spending.
 *
 * Spent is the sum of expense transactions in the base currency.
 */
class InsightsCatalog
{
    private const int TREND_MONTHS = 12;

    public function __construct(
        private MoneyConverter $converter,
    ) {}

    /**
     * @return array{
     *     comparison: array<string, mixed>,
     *     trends: array<string, mixed>
     * }
     */
    public function page(User $user, InsightsQuery $query): array
    {
        $workspace = $user->workspace;
        $display = $user->display_currency;
        $categories = Category::query()
            ->whereBelongsTo($workspace)
            ->ordered()
            ->get();

        return [
            'comparison' => $this->comparison($workspace, $categories, $query, $display),
            'trends' => $this->trends($workspace, $display),
        ];
    }

    /**
     * @param  EloquentCollection<int, Category>  $categories
     * @return array<string, mixed>
     */
    private function comparison(Workspace $workspace, EloquentCollection $categories, InsightsQuery $query, Currency $display): array
    {
        $transactions = $this->between($workspace, $query->left, $query->right)
            ->filter(fn (Transaction $transaction): bool => $transaction->type === TransactionType::Expense)
            ->values();

        /** @var array<int|string, string> $left */
        $left = [];
        /** @var array<int|string, string> $right */
        $right = [];

        foreach ($transactions as $transaction) {
            $key = $transaction->category_id ?? 'none';
            $amount = Decimal::of((string) $transaction->base_amount);
            $date = $transaction->booked_on->toDateString();

            if ($this->contains($query->left, $date)) {
                $left[$key] = ($left[$key] ?? null) === null
                    ? $amount->toString(MoneyConverter::SCALE)
                    : Decimal::of($left[$key])->add($amount)->toString(MoneyConverter::SCALE);
            }

            if ($this->contains($query->right, $date)) {
                $right[$key] = ($right[$key] ?? null) === null
                    ? $amount->toString(MoneyConverter::SCALE)
                    : Decimal::of($right[$key])->add($amount)->toString(MoneyConverter::SCALE);
            }
        }

        $activity = $left + $right;
        $expenseCategories = $categories
            ->filter(fn (Category $category): bool => $category->kind === CategoryKind::Expense)
            ->values();
        $visible = $expenseCategories
            ->filter(function (Category $category) use ($activity): bool {
                if ($category->archived_at === null) {
                    return true;
                }

                return array_key_exists($category->id, $activity);
            })
            ->sortBy(fn (Category $category): array => [
                $category->archived_at === null ? 0 : 1,
                $category->position,
                $category->id,
            ])
            ->values();

        $rows = [];

        foreach ($visible as $category) {
            $rows[] = $this->comparisonRow(
                $category->id,
                $category->name,
                $category->description,
                $category->archived_at !== null,
                $left[$category->id] ?? '0.00',
                $right[$category->id] ?? '0.00',
                $display,
            );
        }

        if (array_key_exists('none', $activity)) {
            $rows[] = $this->comparisonRow(
                null,
                'Uncategorised',
                null,
                false,
                $left['none'] ?? '0.00',
                $right['none'] ?? '0.00',
                $display,
            );
        }

        $leftTotal = $this->sumValues($left);
        $rightTotal = $this->sumValues($right);
        $change = $this->change($leftTotal, $rightTotal);

        return [
            'left' => $this->month($query->left),
            'right' => $this->month($query->right),
            'rows' => $rows,
            'total' => [
                'left' => $this->figure($leftTotal, $display),
                'right' => $this->figure($rightTotal, $display),
                'change' => $this->figure($change['base'], $display),
                'direction' => $change['direction'],
                'direction_label' => $change['label'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function comparisonRow(
        ?int $id,
        string $name,
        ?string $description,
        bool $archived,
        string $left,
        string $right,
        Currency $display,
    ): array {
        $change = $this->change($left, $right);

        return [
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'archived' => $archived,
            'left' => $this->figure($left, $display),
            'right' => $this->figure($right, $display),
            'change' => $this->figure($change['base'], $display),
            'direction' => $change['direction'],
            'direction_label' => $change['label'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function trends(Workspace $workspace, Currency $display): array
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
                'income' => $this->figure($bucket['income']->toString(MoneyConverter::SCALE), $display),
                'spent' => $this->figure($bucket['spent']->toString(MoneyConverter::SCALE), $display),
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
     * Expenses and income dated in either month. The same month is not doubled in SQL.
     *
     * @return EloquentCollection<int, Transaction>
     */
    private function between(Workspace $workspace, CalendarMonth $left, CalendarMonth $right): EloquentCollection
    {
        return Transaction::query()
            ->whereBelongsTo($workspace)
            ->whereIn('type', [TransactionType::Income->value, TransactionType::Expense->value])
            ->where(function (Builder $query) use ($left, $right): void {
                $this->constrainMonth($query, $left);
                $query->orWhere(function (Builder $query) use ($right): void {
                    $this->constrainMonth($query, $right);
                });
            })
            ->get(['id', 'category_id', 'type', 'amount', 'currency', 'base_amount', 'booked_on']);
    }

    /**
     * @param  Builder<Transaction>  $query
     */
    private function constrainMonth(Builder $query, CalendarMonth $month): void
    {
        $query->where('booked_on', '>=', $month->start()->toDateString())
            ->where('booked_on', '<', $month->endExclusive()->toDateString());
    }

    private function contains(CalendarMonth $month, string $date): bool
    {
        return $date >= $month->start()->toDateString()
            && $date < $month->endExclusive()->toDateString();
    }

    /**
     * @param  array<int|string, string>  $values
     */
    private function sumValues(array $values): string
    {
        $total = Decimal::of('0');

        foreach ($values as $value) {
            $total = $total->add(Decimal::of($value));
        }

        return $total->toString(MoneyConverter::SCALE);
    }

    /**
     * @return array{base: string, direction: 'up'|'down'|'same', label: string}
     */
    private function change(string $left, string $right): array
    {
        $difference = Decimal::of($right)->subtract(Decimal::of($left))->toString(MoneyConverter::SCALE);
        $value = Decimal::of($difference);

        if ($value->isZero()) {
            return ['base' => $difference, 'direction' => 'same', 'label' => 'No change'];
        }

        if ($value->isNegative()) {
            return ['base' => $difference, 'direction' => 'down', 'label' => 'Spent less'];
        }

        return ['base' => $difference, 'direction' => 'up', 'label' => 'Spent more'];
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
    private function figure(string $baseAmount, Currency $display): array
    {
        $normalized = Decimal::of($baseAmount)->toString(MoneyConverter::SCALE);
        $presented = $this->converter->present($normalized, Currency::base(), $normalized, $display);

        return [
            ...$presented->toArray(),
            'negative' => Decimal::of($presented->amount)->isNegative(),
            'base_amount' => $normalized,
        ];
    }
}
