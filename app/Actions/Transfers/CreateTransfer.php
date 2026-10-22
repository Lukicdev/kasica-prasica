<?php

namespace App\Actions\Transfers;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Support\TransferAmounts;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CreateTransfer
{
    public function __construct(private TransferAmounts $amounts) {}

    /**
     * @param  array{
     *     amount: string,
     *     currency: string,
     *     booked_on: CarbonInterface|string,
     *     note?: string|null,
     *     person?: User|null
     * }  $attributes
     */
    public function handle(Account $from, Account $to, User $actor, array $attributes): Transaction
    {
        $this->guardWorkspace($actor, $from);
        $this->guardWorkspace($actor, $to);
        $this->guardDistinct($from, $to);
        $this->guardSelectable($from, 'from_account_id');
        $this->guardSelectable($to, 'to_account_id');

        $person = $attributes['person'] ?? $actor;
        $this->guardWorkspace($person, $from);

        $bookedOn = Carbon::parse($attributes['booked_on']);
        $resolved = $this->amounts->resolve(
            $from,
            $to,
            $attributes['amount'],
            $attributes['currency'],
            $bookedOn,
        );
        $note = trim((string) ($attributes['note'] ?? ''));

        return Transaction::query()->create([
            'workspace_id' => $from->workspace_id,
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
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    private function guardWorkspace(User $user, Account $account): void
    {
        if ($user->workspace_id !== $account->workspace_id) {
            throw new AuthorizationException('This account is outside your workspace.');
        }
    }

    private function guardDistinct(Account $from, Account $to): void
    {
        if ($from->id === $to->id) {
            throw ValidationException::withMessages([
                'to_account_id' => 'Choose a different account.',
            ]);
        }
    }

    private function guardSelectable(Account $account, string $field): void
    {
        if ($account->archived_at !== null) {
            throw ValidationException::withMessages([
                $field => 'This account cannot be used for a transfer.',
            ]);
        }
    }
}
