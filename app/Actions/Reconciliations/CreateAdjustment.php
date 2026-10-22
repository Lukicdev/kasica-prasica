<?php

namespace App\Actions\Reconciliations;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Support\AccountBalance;
use App\Support\Decimal;
use App\Support\MoneyConverter;
use App\Support\TransactionAmounts;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CreateAdjustment
{
    public function __construct(
        private AccountBalance $balances,
        private TransactionAmounts $amounts,
    ) {}

    /**
     * @param  array{
     *     real_balance: string,
     *     booked_on: CarbonInterface|string,
     *     note?: string|null,
     *     person?: User|null
     * }  $attributes
     */
    public function handle(Account $account, User $actor, array $attributes): Transaction
    {
        $person = $attributes['person'] ?? $actor;

        if ($person->workspace_id !== $account->workspace_id) {
            throw new AuthorizationException('This account is outside your workspace.');
        }

        $real = Decimal::of($attributes['real_balance'])->toString(MoneyConverter::SCALE);
        $difference = Decimal::of($real)->subtract(Decimal::of($this->balances->of($account)));

        if ($difference->isZero()) {
            throw ValidationException::withMessages([
                'real_balance' => 'This matches the balance in the app.',
            ]);
        }

        $bookedOn = Carbon::parse($attributes['booked_on']);
        $resolved = $this->amounts->resolve(
            $account,
            $difference->toString(MoneyConverter::SCALE),
            $account->currency,
            $bookedOn,
        );
        $note = trim((string) ($attributes['note'] ?? ''));

        return Transaction::query()->create([
            'workspace_id' => $account->workspace_id,
            'account_id' => $account->id,
            'category_id' => null,
            'person_id' => $person->id,
            'type' => TransactionType::Adjustment,
            'amount' => $resolved['amount'],
            'currency' => $account->currency,
            'account_amount' => $resolved['account_amount'],
            'base_amount' => $resolved['base_amount'],
            'exchange_rate' => $resolved['exchange_rate'],
            'booked_on' => $bookedOn->toDateString(),
            'note' => $note === '' ? null : $note,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }
}
