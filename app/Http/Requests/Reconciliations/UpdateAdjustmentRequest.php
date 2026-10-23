<?php

namespace App\Http\Requests\Reconciliations;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Money\Decimal;
use App\Money\MoneyConverter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class UpdateAdjustmentRequest extends FormRequest
{
    private ?Account $resolvedAccount = null;

    private ?User $resolvedPerson = null;

    private ?string $resolvedAmount = null;

    public function authorize(): bool
    {
        $transaction = $this->route('transaction');

        return $transaction instanceof Transaction
            && $transaction->type === TransactionType::Adjustment
            && ($this->user()?->can('update', $transaction) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'regex:/^-?\d{1,13}(\.\d{1,2})?$/'],
            'account_id' => ['required', 'integer'],
            'booked_on' => ['required', 'date'],
            'person_id' => ['required', 'integer'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Enter the amount that balances the account.',
            'amount.regex' => 'Enter an amount with up to two decimal places.',
            'account_id.required' => 'Choose an account.',
            'account_id.integer' => 'Choose an account.',
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
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $this->resolveAdjustment($validator);
            },
        ];
    }

    public function account(): Account
    {
        if (! $this->resolvedAccount instanceof Account) {
            throw ValidationException::withMessages([
                'account_id' => 'Choose an account.',
            ]);
        }

        return $this->resolvedAccount;
    }

    /**
     * @return array{
     *     amount: string,
     *     booked_on: string,
     *     note: string|null,
     *     person: User
     * }
     */
    public function adjustmentAttributes(): array
    {
        if (! $this->resolvedPerson instanceof User || $this->resolvedAmount === null) {
            throw ValidationException::withMessages([
                'amount' => 'Enter the amount that balances the account.',
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
            'booked_on' => $bookedOn,
            'note' => $note === '' ? null : $note,
            'person' => $this->resolvedPerson,
        ];
    }

    private function resolveAdjustment(Validator $validator): void
    {
        $actor = $this->user();
        $existing = $this->route('transaction');

        if (! $actor instanceof User || ! $existing instanceof Transaction) {
            $validator->errors()->add('account_id', 'Choose an account.');

            return;
        }

        $account = Account::query()
            ->whereBelongsTo($actor->workspace)
            ->find($this->integer('account_id'));

        if (! $account instanceof Account) {
            $validator->errors()->add('account_id', 'Choose an account.');

            return;
        }

        $moving = $account->id !== $existing->account_id;

        if ($moving && $account->archived_at !== null) {
            $validator->errors()->add('account_id', 'This account cannot be used for a new balance adjustment.');

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

        if (Decimal::of($amount)->isZero()) {
            $validator->errors()->add('amount', 'Enter an amount other than zero.');

            return;
        }

        $this->resolvedAccount = $account;
        $this->resolvedPerson = $person;
        $this->resolvedAmount = $amount;
    }
}
