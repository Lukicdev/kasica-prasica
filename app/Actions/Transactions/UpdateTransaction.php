<?php

namespace App\Actions\Transactions;

use App\Enums\Currency;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Support\TransactionAmounts;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class UpdateTransaction
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
     *     category?: Category|null
     * }  $attributes
     */
    public function handle(Transaction $transaction, Account $account, User $actor, array $attributes): Transaction
    {
        if ($actor->workspace_id !== $transaction->workspace_id || $account->workspace_id !== $transaction->workspace_id) {
            throw new AuthorizationException('This account is outside your workspace.');
        }

        $moving = $account->id !== $transaction->account_id;

        if ($moving && ($account->archived_at !== null || $account->is_imported_history)) {
            throw ValidationException::withMessages([
                'account' => 'This account cannot be used for new transactions.',
            ]);
        }

        $person = $attributes['person'] ?? $transaction->person ?? $actor;
        if ($person->workspace_id !== $transaction->workspace_id) {
            throw new AuthorizationException('This account is outside your workspace.');
        }

        $category = array_key_exists('category', $attributes)
            ? $attributes['category']
            : ($transaction->category_id === null ? null : $transaction->category);

        if ($category instanceof Category) {
            if ($category->workspace_id !== $transaction->workspace_id) {
                throw new AuthorizationException('This category is outside your workspace.');
            }

            if ($category->kind->value !== $attributes['type']->value) {
                throw ValidationException::withMessages([
                    'category_id' => 'Choose a category of the same kind.',
                ]);
            }

            if ($category->id !== $transaction->category_id && $category->archived_at !== null) {
                throw ValidationException::withMessages([
                    'category_id' => 'This category cannot be used for new transactions.',
                ]);
            }
        }

        $bookedOn = Carbon::parse($attributes['booked_on']);
        $resolved = $this->amounts->resolve(
            $account,
            $attributes['amount'],
            $attributes['currency'],
            $bookedOn,
        );
        $note = trim((string) ($attributes['note'] ?? ''));

        $transaction->update([
            'account_id' => $account->id,
            'category_id' => $category?->id,
            'person_id' => $person->id,
            'type' => $attributes['type'],
            'amount' => $resolved['amount'],
            'currency' => $attributes['currency'],
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
