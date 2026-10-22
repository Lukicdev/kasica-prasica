<?php

namespace App\Support;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The transfer form: two accounts, and the amount that leaves the first.
 */
class TransferCatalog
{
    public function __construct(private AccountBalance $balances) {}

    /**
     * @param  array{from?: int|null, to?: int|null, date?: string}  $prefill
     * @return array{
     *     transfer: array{
     *         id: int,
     *         amount: string,
     *         currency: string,
     *         from_account_id: int,
     *         to_account_id: int|null,
     *         account_amount: string,
     *         destination_amount: string|null,
     *         booked_on: string,
     *         person_id: int|null,
     *         note: string|null
     *     }|null,
     *     accounts: list<array{id: int, name: string, currency: string, balance: string, archived: bool}>,
     *     people: list<array{id: int, name: string}>,
     *     defaults: array{
     *         from_account_id: int|null,
     *         to_account_id: int|null,
     *         person_id: int,
     *         date: string,
     *         currency: string
     *     },
     *     needs_second_account: bool
     * }
     */
    public function form(User $user, ?Transaction $transfer = null, array $prefill = []): array
    {
        $workspace = $user->workspace;
        $transfer?->loadMissing(['account', 'destinationAccount']);

        $accounts = Account::query()
            ->whereBelongsTo($workspace)
            ->orderBy('name')
            ->get();

        foreach ([$transfer?->account, $transfer?->destinationAccount] as $current) {
            if ($current instanceof Account && ! $accounts->contains(fn (Account $account): bool => $account->id === $current->id)) {
                $accounts->push($current);
            }
        }

        $kept = array_filter([
            $transfer?->account_id,
            $transfer?->destination_account_id,
        ]);

        $formAccounts = $accounts
            ->filter(function (Account $account) use ($kept): bool {
                if (in_array($account->id, $kept, true)) {
                    return true;
                }

                return $account->archived_at === null;
            })
            ->values();

        $balances = $this->balances->forAccounts($formAccounts);
        $people = User::query()
            ->whereBelongsTo($workspace)
            ->orderBy('name')
            ->get(['id', 'name']);
        $selectable = $formAccounts->filter(
            fn (Account $account): bool => $account->archived_at === null,
        );

        return [
            'transfer' => $transfer === null ? null : $this->values($transfer),
            'accounts' => array_values($formAccounts->map(fn (Account $account): array => [
                'id' => $account->id,
                'name' => $account->archived_at === null ? $account->name : $account->name.' (archived)',
                'currency' => $account->currency,
                'balance' => $balances[$account->id],
                'archived' => $account->archived_at !== null,
            ])->all()),
            'people' => array_values($people->map(fn (User $person): array => [
                'id' => $person->id,
                'name' => $person->name,
            ])->all()),
            'defaults' => $this->defaults($user, $selectable, $prefill),
            'needs_second_account' => $transfer === null && $selectable->count() < 2,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     amount: string,
     *     currency: string,
     *     exchange_rate: string,
     *     from_account_id: int,
     *     to_account_id: int|null,
     *     account_amount: string,
     *     destination_amount: string|null,
     *     booked_on: string,
     *     person_id: int|null,
     *     note: string|null
     * }
     */
    private function values(Transaction $transfer): array
    {
        return [
            'id' => $transfer->id,
            'amount' => Decimal::of((string) $transfer->amount)->toString(MoneyConverter::SCALE),
            'currency' => $transfer->currency,
            'exchange_rate' => Rate::trim((string) $transfer->exchange_rate),
            'from_account_id' => $transfer->account_id,
            'to_account_id' => $transfer->destination_account_id,
            'account_amount' => Decimal::of((string) $transfer->account_amount)->toString(MoneyConverter::SCALE),
            'destination_amount' => $transfer->destination_amount === null
                ? null
                : Decimal::of((string) $transfer->destination_amount)->toString(MoneyConverter::SCALE),
            'booked_on' => $transfer->booked_on->toDateString(),
            'person_id' => $transfer->person_id,
            'note' => $transfer->note,
        ];
    }

    /**
     * @param  Collection<int, Account>  $accounts
     * @param  array{from?: int|null, to?: int|null, date?: string}  $prefill
     * @return array{from_account_id: int|null, to_account_id: int|null, person_id: int, date: string, currency: string}
     */
    private function defaults(User $user, Collection $accounts, array $prefill): array
    {
        $from = $this->account($user, $accounts, $prefill['from'] ?? null);
        $to = $this->otherAccount($accounts, $from, $prefill['to'] ?? null);

        return [
            'from_account_id' => $from?->id,
            'to_account_id' => $to?->id,
            'person_id' => $user->id,
            'date' => $this->date($prefill['date'] ?? null),
            'currency' => $from instanceof Account ? $from->currency : $user->workspace->base_currency,
        ];
    }

    /**
     * @param  Collection<int, Account>  $accounts
     */
    private function account(User $user, Collection $accounts, ?int $requested): ?Account
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
     * @param  Collection<int, Account>  $accounts
     */
    private function otherAccount(Collection $accounts, ?Account $from, ?int $requested): ?Account
    {
        if ($requested !== null && ($from === null || $requested !== $from->id)) {
            $chosen = $accounts->firstWhere('id', $requested);

            if ($chosen instanceof Account) {
                return $chosen;
            }
        }

        $other = $accounts->first(fn (Account $account): bool => $from === null || $account->id !== $from->id);

        return $other instanceof Account ? $other : null;
    }

    private function date(?string $date): string
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
}
