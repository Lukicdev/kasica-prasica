<?php

namespace App\Http\Requests\Reconciliations;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Decimal;
use App\Support\MoneyConverter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class StoreReconciliationRequest extends FormRequest
{
    private ?User $resolvedPerson = null;

    private ?string $resolvedBalance = null;

    public function authorize(): bool
    {
        $account = $this->route('account');
        $user = $this->user();

        return $account instanceof Account
            && $user->can('view', $account)
            && $user->can('create', Transaction::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'real_balance' => ['required', 'regex:/^-?\d{1,13}(\.\d{1,2})?$/'],
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
            'real_balance.required' => 'Enter the real balance.',
            'real_balance.regex' => 'Enter a balance with up to two decimal places.',
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

                $actor = $this->user();

                if (! $actor instanceof User) {
                    $validator->errors()->add('person_id', 'Choose a person.');

                    return;
                }

                $person = User::query()
                    ->whereBelongsTo($actor->workspace)
                    ->find($this->integer('person_id'));

                if (! $person instanceof User) {
                    $validator->errors()->add('person_id', 'Choose a person.');

                    return;
                }

                $this->resolvedPerson = $person;
                $this->resolvedBalance = Decimal::of($this->string('real_balance')->toString())
                    ->toString(MoneyConverter::SCALE);
            },
        ];
    }

    /**
     * @return array{
     *     real_balance: string,
     *     booked_on: string,
     *     note: string|null,
     *     person: User
     * }
     */
    public function adjustmentAttributes(): array
    {
        if (! $this->resolvedPerson instanceof User || $this->resolvedBalance === null) {
            throw ValidationException::withMessages([
                'real_balance' => 'Enter the real balance.',
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
            'real_balance' => $this->resolvedBalance,
            'booked_on' => $bookedOn,
            'note' => $note === '' ? null : $note,
            'person' => $this->resolvedPerson,
        ];
    }
}
