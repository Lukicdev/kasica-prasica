<?php

namespace App\Concerns;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Rules\EnabledCurrency;
use App\Support\Decimal;
use App\Support\MoneyConverter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

/**
 * @phpstan-require-extends FormRequest
 */
trait TransactionValidationRules
{
    private ?Account $resolvedAccount = null;

    private ?Category $resolvedCategory = null;

    private ?User $resolvedPerson = null;

    private ?string $resolvedAmount = null;

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function transactionRules(): array
    {
        return [
            'type' => ['required', Rule::in([TransactionType::Income->value, TransactionType::Expense->value])],
            'amount' => ['required', 'regex:/^-?\d{1,13}(\.\d{1,2})?$/'],
            'refund' => ['sometimes', 'boolean'],
            'currency' => ['required', 'string', 'size:3', new EnabledCurrency($this->user())],
            'account_id' => ['required', 'integer'],
            'category_id' => ['required', 'integer'],
            'booked_on' => ['required', 'date'],
            'person_id' => ['required', 'integer'],
            'note' => ['nullable', 'string', 'max:1000'],
            'recurring' => ['sometimes', 'boolean'],
            'another' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function transactionMessages(): array
    {
        return [
            'type.required' => 'Choose income or expense.',
            'type.in' => 'Choose income or expense.',
            'amount.required' => 'Enter an amount.',
            'amount.regex' => 'Enter an amount with up to two decimal places.',
            'currency.required' => 'Choose a currency.',
            'currency.size' => 'Choose a currency.',
            'account_id.required' => 'Choose an account.',
            'account_id.integer' => 'Choose an account.',
            'category_id.required' => 'Choose a category.',
            'category_id.integer' => 'Choose a category.',
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
    protected function transactionChecks(?Transaction $existing): array
    {
        return [
            function (Validator $validator) use ($existing): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $this->resolveTransaction($validator, $existing);
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
     *     type: TransactionType,
     *     amount: string,
     *     currency: string,
     *     booked_on: string,
     *     note: string|null,
     *     person: User,
     *     category: Category,
     *     recurring: bool
     * }
     */
    public function transactionAttributes(): array
    {
        if (
            ! $this->resolvedCategory instanceof Category
            || ! $this->resolvedPerson instanceof User
            || $this->resolvedAmount === null
        ) {
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
            'type' => TransactionType::from($this->string('type')->toString()),
            'amount' => $this->resolvedAmount,
            'currency' => strtoupper($this->string('currency')->toString()),
            'booked_on' => $bookedOn,
            'note' => $note === '' ? null : $note,
            'person' => $this->resolvedPerson,
            'category' => $this->resolvedCategory,
            'recurring' => $this->boolean('recurring'),
        ];
    }

    private function resolveTransaction(Validator $validator, ?Transaction $existing): void
    {
        $actor = $this->user();

        if (! $actor instanceof User) {
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

        $moving = $existing === null || $existing->account_id !== $account->id;

        if ($moving && $account->archived_at !== null) {
            $validator->errors()->add('account_id', 'This account cannot be used for new transactions.');

            return;
        }

        $category = Category::query()
            ->whereBelongsTo($actor->workspace)
            ->find($this->integer('category_id'));

        if (! $category instanceof Category) {
            $validator->errors()->add('category_id', 'Choose a category.');

            return;
        }

        $type = TransactionType::tryFrom($this->string('type')->toString());

        if (! $type instanceof TransactionType || ! $type->isIncomeOrExpense()) {
            $validator->errors()->add('type', 'Choose income or expense.');

            return;
        }

        if ($category->kind->value !== $type->value) {
            $validator->errors()->add('category_id', 'Choose a category of the same kind.');

            return;
        }

        $sameCategory = $existing !== null && $existing->category_id === $category->id;

        if (! $sameCategory && $category->archived_at !== null) {
            $validator->errors()->add('category_id', 'This category cannot be used for new transactions.');

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

        if ($type === TransactionType::Expense && $this->boolean('refund') && ! Decimal::of($amount)->isNegative()) {
            $amount = Decimal::of('0')->subtract(Decimal::of($amount))->toString(MoneyConverter::SCALE);
        }

        if (Decimal::of($amount)->isZero()) {
            $validator->errors()->add('amount', 'Enter an amount other than zero.');

            return;
        }

        if ($type === TransactionType::Income && Decimal::of($amount)->isNegative()) {
            $validator->errors()->add('amount', 'Enter an income amount greater than zero.');

            return;
        }

        $this->resolvedAccount = $account;
        $this->resolvedCategory = $category;
        $this->resolvedPerson = $person;
        $this->resolvedAmount = $amount;
    }
}
