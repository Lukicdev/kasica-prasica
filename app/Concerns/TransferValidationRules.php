<?php

namespace App\Concerns;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Money\Decimal;
use App\Money\MoneyConverter;
use App\Rules\EnabledCurrency;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

/**
 * @phpstan-require-extends FormRequest
 */
trait TransferValidationRules
{
    private ?Account $resolvedFrom = null;

    private ?Account $resolvedTo = null;

    private ?User $resolvedPerson = null;

    private ?string $resolvedAmount = null;

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function transferRules(): array
    {
        return [
            'amount' => ['required', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
            'currency' => ['required', 'string', 'size:3', new EnabledCurrency($this->user())],
            'from_account_id' => ['required', 'integer'],
            'to_account_id' => ['required', 'integer'],
            'booked_on' => ['required', 'date'],
            'person_id' => ['required', 'integer'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function transferMessages(): array
    {
        return [
            'amount.required' => 'Enter an amount.',
            'amount.regex' => 'Enter an amount greater than zero, with up to two decimal places.',
            'currency.required' => 'Choose a currency.',
            'currency.size' => 'Choose a currency.',
            'from_account_id.required' => 'Choose the account the money leaves.',
            'from_account_id.integer' => 'Choose the account the money leaves.',
            'to_account_id.required' => 'Choose the account the money enters.',
            'to_account_id.integer' => 'Choose the account the money enters.',
            'booked_on.required' => 'Enter a date.',
            'booked_on.date' => 'Enter a date.',
            'person_id.required' => 'Choose a person.',
            'person_id.integer' => 'Choose a person.',
            'note.max' => 'Keep the note under 1000 characters.',
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    protected function transferChecks(?Transaction $existing): array
    {
        return [
            function (Validator $validator) use ($existing): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $this->resolveTransfer($validator, $existing);
            },
        ];
    }

    public function fromAccount(): Account
    {
        if (! $this->resolvedFrom instanceof Account) {
            throw ValidationException::withMessages([
                'from_account_id' => 'Choose the account the money leaves.',
            ]);
        }

        return $this->resolvedFrom;
    }

    public function toAccount(): Account
    {
        if (! $this->resolvedTo instanceof Account) {
            throw ValidationException::withMessages([
                'to_account_id' => 'Choose the account the money enters.',
            ]);
        }

        return $this->resolvedTo;
    }

    /**
     * @return array{
     *     amount: string,
     *     currency: string,
     *     booked_on: string,
     *     note: string|null,
     *     person: User
     * }
     */
    public function transferAttributes(): array
    {
        if (! $this->resolvedPerson instanceof User || $this->resolvedAmount === null) {
            throw ValidationException::withMessages([
                'amount' => 'Enter an amount.',
            ]);
        }

        $bookedOn = $this->date('booked_on')?->toDateString();
        $note = trim($this->string('note')->toString());

        if ($bookedOn === null) {
            throw ValidationException::withMessages([
                'booked_on' => 'Enter a date.',
            ]);
        }

        return [
            'amount' => $this->resolvedAmount,
            'currency' => strtoupper($this->string('currency')->toString()),
            'booked_on' => $bookedOn,
            'note' => $note === '' ? null : $note,
            'person' => $this->resolvedPerson,
        ];
    }

    private function resolveTransfer(Validator $validator, ?Transaction $existing): void
    {
        $actor = $this->user();

        if (! $actor instanceof User) {
            $validator->errors()->add('from_account_id', 'Choose the account the money leaves.');

            return;
        }

        $from = Account::query()
            ->whereBelongsTo($actor->workspace)
            ->find($this->integer('from_account_id'));
        $to = Account::query()
            ->whereBelongsTo($actor->workspace)
            ->find($this->integer('to_account_id'));

        if (! $from instanceof Account) {
            $validator->errors()->add('from_account_id', 'Choose the account the money leaves.');

            return;
        }

        if (! $to instanceof Account) {
            $validator->errors()->add('to_account_id', 'Choose the account the money enters.');

            return;
        }

        if ($from->id === $to->id) {
            $validator->errors()->add('to_account_id', 'Choose a different account.');

            return;
        }

        if ($this->accountBlocked($from, $existing?->account_id)) {
            $validator->errors()->add('from_account_id', 'This account cannot be used for a transfer.');

            return;
        }

        if ($this->accountBlocked($to, $existing?->destination_account_id)) {
            $validator->errors()->add('to_account_id', 'This account cannot be used for a transfer.');

            return;
        }

        $person = User::query()
            ->whereBelongsTo($actor->workspace)
            ->find($this->integer('person_id'));

        if (! $person instanceof User) {
            $validator->errors()->add('person_id', 'Choose a person.');

            return;
        }

        $amount = Decimal::of($this->string('amount')->toString())->toString(MoneyConverter::SCALE);

        if (Decimal::of($amount)->isZero() || Decimal::of($amount)->isNegative()) {
            $validator->errors()->add('amount', 'Enter an amount greater than zero.');

            return;
        }

        $this->resolvedFrom = $from;
        $this->resolvedTo = $to;
        $this->resolvedPerson = $person;
        $this->resolvedAmount = $amount;
    }

    private function accountBlocked(Account $account, ?int $currentId): bool
    {
        if ($account->id === $currentId) {
            return false;
        }

        return $account->archived_at !== null;
    }
}
