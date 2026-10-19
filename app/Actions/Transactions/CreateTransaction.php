<?php

namespace App\Actions\Transactions;

use App\Enums\Currency;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Support\TransactionAmounts;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CreateTransaction
{
    public function __construct(private TransactionAmounts $amounts) {}

    /**
     * @param  array{
     *     type: TransactionType,
     *     amount: string,
     *     currency: Currency,
     *     booked_on: CarbonInterface|string,
     *     note?: string|null,
     *     person?: User|null,
     *     account_amount?: string|null
     * }  $attributes
     */
    public function handle(Account $account, User $actor, array $attributes): Transaction
    {
        $this->guardWorkspace($actor, $account->workspace_id);

        if ($account->archived_at !== null || $account->is_imported_history) {
            throw ValidationException::withMessages([
                'account' => 'This account cannot be used for new transactions.',
            ]);
        }

        $person = $attributes['person'] ?? $actor;
        $this->guardWorkspace($person, $account->workspace_id);

        $bookedOn = Carbon::parse($attributes['booked_on']);
        $resolved = $this->amounts->resolve(
            $account,
            $attributes['amount'],
            $attributes['currency'],
            $attributes['account_amount'] ?? null,
            $bookedOn,
        );
        $note = trim((string) ($attributes['note'] ?? ''));

        return Transaction::query()->create([
            'workspace_id' => $account->workspace_id,
            'account_id' => $account->id,
            'person_id' => $person->id,
            'type' => $attributes['type'],
            'amount' => $resolved['amount'],
            'currency' => $attributes['currency'],
            'account_amount' => $resolved['account_amount'],
            'base_amount' => $resolved['base_amount'],
            'exchange_rate' => $resolved['exchange_rate'],
            'booked_on' => $bookedOn->toDateString(),
            'note' => $note === '' ? null : $note,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }

    private function guardWorkspace(User $user, int $workspaceId): void
    {
        if ($user->workspace_id !== $workspaceId) {
            throw new AuthorizationException('This account is outside your workspace.');
        }
    }
}
