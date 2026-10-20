<?php

namespace App\Support;

use App\Enums\Currency;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;

/**
 * Reconciliation compares the calculated balance with the balance the user counted.
 */
class ReconciliationCatalog
{
    public function __construct(
        private AccountBalance $balances,
        private MoneyFormatter $formatter,
    ) {}

    /**
     * @return array{
     *     account: array{
     *         id: int,
     *         name: string,
     *         currency: string,
     *         balance: array{amount: string, currency: string, formatted: string, negative: bool}
     *     },
     *     people: list<array{id: int, name: string}>,
     *     defaults: array{person_id: int, date: string, note: string}
     * }
     */
    public function createForm(User $user, Account $account): array
    {
        return [
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'currency' => $account->currency->value,
                'balance' => $this->money($this->balances->of($account), $account->currency),
            ],
            'people' => $this->people($user),
            'defaults' => [
                'person_id' => $user->id,
                'date' => now()->toDateString(),
                'note' => 'Reconciliation',
            ],
        ];
    }

    /**
     * @return array{
     *     adjustment: array{
     *         id: int,
     *         amount: string,
     *         account_id: int,
     *         booked_on: string,
     *         person_id: int|null,
     *         note: string|null
     *     },
     *     accounts: list<array{id: int, name: string, currency: string, balance: string, archived: bool}>,
     *     people: list<array{id: int, name: string}>
     * }
     */
    public function editForm(User $user, Transaction $adjustment): array
    {
        $adjustment->loadMissing('account');
        $workspace = $user->workspace;
        $accounts = Account::query()
            ->whereBelongsTo($workspace)
            ->orderBy('name')
            ->get();
        $current = $adjustment->account;

        if (! $accounts->contains(fn (Account $account): bool => $account->id === $current->id)) {
            $accounts->push($current);
        }

        $formAccounts = $accounts
            ->filter(function (Account $account) use ($adjustment): bool {
                if ($account->id === $adjustment->account_id) {
                    return true;
                }

                return $account->archived_at === null;
            })
            ->values();
        $balances = $this->balances->forAccounts($formAccounts);

        return [
            'adjustment' => [
                'id' => $adjustment->id,
                'amount' => Decimal::of((string) $adjustment->account_amount)->toString(MoneyConverter::SCALE),
                'account_id' => $adjustment->account_id,
                'booked_on' => $adjustment->booked_on->toDateString(),
                'person_id' => $adjustment->person_id,
                'note' => $adjustment->note,
            ],
            'accounts' => array_values($formAccounts->map(fn (Account $account): array => [
                'id' => $account->id,
                'name' => $account->archived_at === null ? $account->name : $account->name.' (archived)',
                'currency' => $account->currency->value,
                'balance' => $balances[$account->id],
                'archived' => $account->archived_at !== null,
            ])->all()),
            'people' => $this->people($user),
        ];
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function people(User $user): array
    {
        return array_values(User::query()
            ->whereBelongsTo($user->workspace)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $person): array => [
                'id' => $person->id,
                'name' => $person->name,
            ])
            ->all());
    }

    /**
     * @return array{amount: string, currency: string, formatted: string, negative: bool}
     */
    private function money(string $amount, Currency $currency): array
    {
        $normalized = Decimal::of($amount)->toString(MoneyConverter::SCALE);

        return [
            'amount' => $normalized,
            'currency' => $currency->value,
            'formatted' => $this->formatter->format($normalized, $currency),
            'negative' => Decimal::of($normalized)->isNegative(),
        ];
    }
}
