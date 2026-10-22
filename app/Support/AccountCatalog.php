<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Workspace;
use Illuminate\Support\Collection;

/**
 * The account page: balances in each account's currency, and one total in the
 * display currency. The total is converted from the base-currency sum.
 */
class AccountCatalog
{
    public function __construct(
        private AccountBalance $balances,
        private MoneyConverter $converter,
        private MoneyFormatter $formatter,
    ) {}

    /**
     * @param  Collection<int, Account>  $accounts
     * @return array{
     *     groups: list<array{
     *         type: string,
     *         label: string,
     *         accounts: list<array{
     *             id: int,
     *             name: string,
     *             icon: string|null,
     *             type: string,
     *             type_label: string,
     *             currency: string,
     *             balance: array{amount: string, currency: string, formatted: string, negative: bool},
     *             display_balance: array{amount: string, currency: string, formatted: string, negative: bool}|null,
     *             archived: bool,
     *             note: string|null
     *         }>
     *     }>,
     *     total: array{amount: string, currency: string, formatted: string, negative: bool}
     * }
     */
    public function list(Collection $accounts, string $display, Workspace $workspace): array
    {
        $balances = $this->balances->forAccounts($accounts);
        $grouped = [];

        $sorted = $accounts->sortBy(fn (Account $account): array => [
            $account->type->order(),
            $account->archived_at === null ? 0 : 1,
            mb_strtolower($account->name),
        ])->values();

        foreach ($sorted as $account) {
            $type = $account->type->value;

            if (! isset($grouped[$type])) {
                $grouped[$type] = [
                    'type' => $type,
                    'label' => $account->type->label(),
                    'accounts' => [],
                ];
            }

            $grouped[$type]['accounts'][] = $this->summary($account, $balances[$account->id], $display, $workspace);
        }

        return [
            'groups' => array_values($grouped),
            'total' => $this->money($this->displayTotal($accounts, $balances, $display, $workspace), $display),
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     icon: string|null,
     *     type: string,
     *     type_label: string,
     *     currency: string,
     *     opening_balance: string,
     *     opening_balance_on: string,
     *     note: string|null,
     *     currency_locked: bool,
     *     archived: bool,
     *     can_delete: bool,
     *     can_transfer: bool,
     *     balance: array{amount: string, currency: string, formatted: string, negative: bool},
     *     updated_by: string|null
     * }
     */
    public function detail(Account $account): array
    {
        $account->loadMissing(['editor:id,name']);
        $editor = $account->editor;
        $updatedBy = null;

        if (
            $editor !== null
            && $account->updated_at !== null
            && $account->created_at !== null
            && $account->updated_at->greaterThan($account->created_at)
        ) {
            $updatedBy = $editor->name;
        }

        return [
            ...$this->form($account),
            'type_label' => $account->type->label(),
            'can_transfer' => $account->archived_at === null,
            'balance' => $this->money($this->balances->of($account), $account->currency),
            'updated_by' => $updatedBy,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     icon: string|null,
     *     type: string,
     *     currency: string,
     *     opening_balance: string,
     *     opening_balance_on: string,
     *     note: string|null,
     *     currency_locked: bool,
     *     archived: bool,
     *     can_delete: bool
     * }
     */
    public function form(Account $account): array
    {
        $transactionCount = $this->movementCount($account);

        return [
            'id' => $account->id,
            'name' => $account->name,
            'icon' => $account->icon,
            'type' => $account->type->value,
            'currency' => $account->currency,
            'opening_balance' => Decimal::of((string) $account->opening_balance)->toString(MoneyConverter::SCALE),
            'opening_balance_on' => $account->opening_balance_on->toDateString(),
            'note' => $account->note,
            'currency_locked' => $transactionCount > 0,
            'archived' => $account->archived_at !== null,
            'can_delete' => $transactionCount === 0,
        ];
    }

    /**
     * @param  Collection<int, Account>  $accounts
     * @param  array<int, string>  $balances
     */
    public function displayTotal(Collection $accounts, array $balances, string $display, Workspace $workspace): string
    {
        $total = Decimal::of('0');

        foreach ($accounts as $account) {
            $balance = $balances[$account->id];
            $base = $account->currency === $workspace->base_currency
                ? Decimal::of($balance)->toString(MoneyConverter::SCALE)
                : $this->converter->baseAmount($balance, $account->currency, $workspace);

            $total = $total->add(Decimal::of($base));
        }

        return $this->converter->displayAmount($total->toString(MoneyConverter::SCALE), $display, $workspace);
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     icon: string|null,
     *     type: string,
     *     type_label: string,
     *     currency: string,
     *     balance: array{amount: string, currency: string, formatted: string, negative: bool},
     *     display_balance: array{amount: string, currency: string, formatted: string, negative: bool}|null,
     *     archived: bool,
     *     note: string|null
     * }
     */
    private function summary(Account $account, string $balance, string $display, Workspace $workspace): array
    {
        return [
            'id' => $account->id,
            'name' => $account->name,
            'icon' => $account->icon,
            'type' => $account->type->value,
            'type_label' => $account->type->label(),
            'currency' => $account->currency,
            'balance' => $this->money($balance, $account->currency),
            'display_balance' => $this->displayBalance($account, $balance, $display, $workspace),
            'archived' => $account->archived_at !== null,
            'note' => $account->note,
        ];
    }

    /**
     * The same balance in the display currency, when that currency differs.
     *
     * @return array{amount: string, currency: string, formatted: string, negative: bool}|null
     */
    private function displayBalance(Account $account, string $balance, string $display, Workspace $workspace): ?array
    {
        if ($account->currency === $display) {
            return null;
        }

        $base = $account->currency === $workspace->base_currency
            ? Decimal::of($balance)->toString(MoneyConverter::SCALE)
            : $this->converter->baseAmount($balance, $account->currency, $workspace);

        return $this->money($this->converter->displayAmount($base, $display, $workspace), $display);
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

    private function movementCount(Account $account): int
    {
        if ($account->transactions_count === null || $account->incoming_transfers_count === null) {
            $account->loadCount(['transactions', 'incomingTransfers']);
        }

        return (int) $account->transactions_count + (int) $account->incoming_transfers_count;
    }
}
