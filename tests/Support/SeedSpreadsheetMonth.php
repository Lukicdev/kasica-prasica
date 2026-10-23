<?php

namespace Tests\Support;

use App\Actions\Accounts\CreateAccount;
use App\Actions\Budgets\StartBudgetMonth;
use App\Actions\Budgets\UpdateCategoryBudget;
use App\Actions\Transactions\CreateTransaction;
use App\Enums\AccountType;
use App\Enums\CategoryKind;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use App\Models\Workspace;
use App\Support\CalendarMonth;
use App\Support\Decimal;
use App\Support\MoneyConverter;
use InvalidArgumentException;
use RuntimeException;

/**
 * Loads the spreadsheet month as real transactions, budgets and accounts.
 *
 * Each spent figure is several transactions, so the overview total is a sum
 * and not a typed-in number. The two salaries belong to different people.
 */
class SeedSpreadsheetMonth
{
    public function __construct(
        private CreateAccount $accounts,
        private CreateTransaction $transactions,
        private StartBudgetMonth $startBudget,
        private UpdateCategoryBudget $budgets,
    ) {}

    public function handle(User $first, User $second, CalendarMonth $month): void
    {
        if ($first->workspace_id !== $second->workspace_id) {
            throw new InvalidArgumentException('Both people must share the workspace.');
        }

        $workspace = $first->workspace;
        $cash = $this->accounts->handle($first, [
            'name' => 'Cash',
            'icon' => 'banknote',
            'type' => AccountType::Cash,
            'currency' => 'RSD',
            'opening_balance' => '0.00',
            'opening_balance_on' => $month->date(),
            'note' => null,
        ]);
        $bank = $this->accounts->handle($first, [
            'name' => 'Bank',
            'icon' => 'landmark',
            'type' => AccountType::Bank,
            'currency' => 'RSD',
            'opening_balance' => '0.00',
            'opening_balance_on' => $month->date(),
            'note' => null,
        ]);

        foreach (SpreadsheetReference::income() as $row) {
            $person = $row['partner'] === 'one' ? $first : $second;
            $this->record(
                $this->category($workspace, $row['name'], CategoryKind::Income),
                $person,
                $row['amount'],
                $month,
                [$cash, $bank],
                TransactionType::Income,
            );
        }

        foreach (SpreadsheetReference::expenses() as $row) {
            $this->record(
                $this->category($workspace, $row['name'], CategoryKind::Expense),
                $first,
                $row['spent'],
                $month,
                [$cash, $bank],
                TransactionType::Expense,
                $second,
            );
        }

        $this->startBudget->handle($first, $month, false);

        foreach (SpreadsheetReference::expenses() as $row) {
            $this->budgets->handle(
                $first,
                $month,
                $this->category($workspace, $row['name'], CategoryKind::Expense),
                $row['budget'],
            );
        }
    }

    /**
     * @param  array{0: Account, 1: Account}  $accounts
     */
    private function record(
        Category $category,
        User $person,
        string $amount,
        CalendarMonth $month,
        array $accounts,
        TransactionType $type,
        ?User $alternate = null,
    ): void {
        $parts = $this->parts($amount);

        foreach ($parts as $index => $part) {
            $actor = $index % 2 === 0 || $alternate === null ? $person : $alternate;
            $day = [3, 13, 23][$index] ?? 3;

            $this->transactions->handle($accounts[$index % 2], $actor, [
                'type' => $type,
                'amount' => $part,
                'currency' => 'RSD',
                'booked_on' => $month->start()->day($day)->toDateString(),
                'note' => $category->name,
                'person' => $person,
                'category' => $category,
            ]);
        }
    }

    /**
     * Three purchases that add back to the exact total, or one when it is small.
     *
     * @return list<string>
     */
    private function parts(string $amount): array
    {
        $normalized = Decimal::of($amount)->toString(MoneyConverter::SCALE);
        $cents = (int) str_replace('.', '', $normalized);

        if ($cents < 300) {
            return [$normalized];
        }

        $part = intdiv($cents, 3);
        $remainder = $cents - ($part * 2);

        return [
            $this->fromCents($part),
            $this->fromCents($part),
            $this->fromCents($remainder),
        ];
    }

    private function fromCents(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    private function category(Workspace $workspace, string $name, CategoryKind $kind): Category
    {
        $category = Category::query()
            ->whereBelongsTo($workspace)
            ->where('name', $name)
            ->where('kind', $kind)
            ->first();

        if (! $category instanceof Category) {
            throw new RuntimeException("Missing {$kind->value} category [{$name}].");
        }

        return $category;
    }
}
