<?php

namespace App\Http\Requests\Recurrences;

use App\Enums\Currency;
use App\Models\Transaction;
use App\Support\CalendarMonth;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmRecurrenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $transaction = $this->route('transaction');

        return $transaction instanceof Transaction
            && ($this->user()?->can('update', $transaction) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'amount' => ['sometimes', 'required', 'regex:/^-?\d{1,13}(\.\d{1,2})?$/'],
            'refund' => ['sometimes', 'boolean'],
            'currency' => ['sometimes', 'required', Rule::enum(Currency::class)],
            'account_id' => ['sometimes', 'required', 'integer'],
            'booked_on' => ['sometimes', 'required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'month.required' => 'Choose a month.',
            'month.regex' => 'Choose a month.',
            'amount.required' => 'Enter an amount.',
            'amount.regex' => 'Enter an amount with up to two decimal places.',
            'currency.required' => 'Choose RSD or EUR.',
            'currency.enum' => 'Choose RSD or EUR.',
            'account_id.required' => 'Choose an account.',
            'account_id.integer' => 'Choose an account.',
            'booked_on.required' => 'Enter a date.',
            'booked_on.date' => 'Enter a date.',
        ];
    }

    public function month(): CalendarMonth
    {
        return CalendarMonth::fromValue($this->string('month')->toString());
    }

    /**
     * Fields the user changed. Anything omitted keeps the repeating transaction.
     *
     * @return array{amount?: string, refund?: bool, currency?: Currency, account_id?: int, booked_on?: string}
     */
    public function changes(): array
    {
        $changes = [];

        if ($this->exists('amount')) {
            $changes['amount'] = $this->string('amount')->toString();
            $changes['refund'] = $this->boolean('refund');
        }

        if ($this->exists('currency')) {
            $changes['currency'] = Currency::from($this->string('currency')->toString());
        }

        if ($this->exists('account_id')) {
            $changes['account_id'] = $this->integer('account_id');
        }

        if ($this->exists('booked_on')) {
            $bookedOn = $this->date('booked_on')?->toDateString();

            if (is_string($bookedOn)) {
                $changes['booked_on'] = $bookedOn;
            }
        }

        return $changes;
    }
}
