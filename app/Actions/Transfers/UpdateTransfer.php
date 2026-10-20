<?php

namespace App\Actions\Transfers;

use App\Enums\Currency;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Support\TransferAmounts;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class UpdateTransfer
{
    public function __construct(private TransferAmounts $amounts) {}

    /**
     * @param  array{
     *     amount: string,
     *     currency: Currency,
     *     booked_on: CarbonInterface|string,
     *     note?: string|null,
     *     person?: User|null
     * }  $attributes
     */
    public function handle(Transaction $transaction, Account $from, Account $to, User $actor, array $attributes): Transaction
    {
        if ($transaction->type !== TransactionType::Transfer) {
            throw ValidationException::withMessages([
                'from_account_id' => 'This entry is not a transfer.',
            ]);
        }

        if (
            $actor->workspace_id !== $transaction->workspace_id
            || $from->workspace_id !== $transaction->workspace_id
            || $to->workspace_id !== $transaction->workspace_id
        ) {
            throw new AuthorizationException('This account is outside your workspace.');
        }

        if ($from->id === $to->id) {
            throw ValidationException::withMessages([
                'to_account_id' => 'Choose a different account.',
            ]);
        }

        $this->guardSelectable($from, $transaction->account_id, 'from_account_id');
        $this->guardSelectable($to, $transaction->destination_account_id, 'to_account_id');

        $person = $attributes['person'] ?? $transaction->person ?? $actor;

        if ($person->workspace_id !== $transaction->workspace_id) {
            throw new AuthorizationException('This account is outside your workspace.');
        }

        $bookedOn = Carbon::parse($attributes['booked_on']);
        $resolved = $this->amounts->resolve(
            $from,
            $to,
            $attributes['amount'],
            $attributes['currency'],
            $bookedOn,
        );
        $note = trim((string) ($attributes['note'] ?? ''));

        $transaction->update([
            'account_id' => $from->id,
            'destination_account_id' => $to->id,
            'category_id' => null,
            'person_id' => $person->id,
            'type' => TransactionType::Transfer,
            'amount' => $resolved['amount'],
            'currency' => $attributes['currency'],
            'account_amount' => $resolved['account_amount'],
            'destination_amount' => $resolved['destination_amount'],
            'base_amount' => $resolved['base_amount'],
            'exchange_rate' => $resolved['exchange_rate'],
            'booked_on' => $bookedOn->toDateString(),
            'note' => $note === '' ? null : $note,
            'updated_by' => $actor->id,
        ]);

        return $transaction;
    }

    private function guardSelectable(Account $account, ?int $currentId, string $field): void
    {
        if ($account->id === $currentId) {
            return;
        }

        if ($account->archived_at !== null) {
            throw ValidationException::withMessages([
                $field => 'This account cannot be used for a transfer.',
            ]);
        }
    }
}
