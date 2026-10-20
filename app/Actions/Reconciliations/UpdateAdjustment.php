<?php

namespace App\Actions\Reconciliations;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Support\TransactionAmounts;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class UpdateAdjustment
{
    public function __construct(private TransactionAmounts $amounts) {}

    /**
     * @param  array{
     *     amount: string,
     *     booked_on: CarbonInterface|string,
     *     note?: string|null,
     *     person?: User|null
     * }  $attributes
     */
    public function handle(Transaction $transaction, Account $account, User $actor, array $attributes): Transaction
    {
        if ($transaction->type !== TransactionType::Adjustment) {
            throw ValidationException::withMessages([
                'amount' => 'This entry is not a balance adjustment.',
            ]);
        }

        if ($actor->workspace_id !== $transaction->workspace_id || $account->workspace_id !== $transaction->workspace_id) {
            throw new AuthorizationException('This account is outside your workspace.');
        }

        if ($account->id !== $transaction->account_id && $account->archived_at !== null) {
            throw ValidationException::withMessages([
                'account_id' => 'This account cannot be used for a new balance adjustment.',
            ]);
        }

        $person = $attributes['person'] ?? $transaction->person ?? $actor;

        if ($person->workspace_id !== $transaction->workspace_id) {
            throw new AuthorizationException('This account is outside your workspace.');
        }

        $bookedOn = Carbon::parse($attributes['booked_on']);
        $resolved = $this->amounts->resolve(
            $account,
            $attributes['amount'],
            $account->currency,
            $bookedOn,
        );
        $note = trim((string) ($attributes['note'] ?? ''));

        $transaction->update([
            'account_id' => $account->id,
            'destination_account_id' => null,
            'destination_amount' => null,
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
            'updated_by' => $actor->id,
        ]);

        return $transaction;
    }
}
