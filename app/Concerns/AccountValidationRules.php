<?php

namespace App\Concerns;

use App\Enums\AccountType;
use App\Enums\Currency;
use App\Support\Decimal;
use App\Support\Icons;
use App\Support\MoneyConverter;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * @phpstan-require-extends FormRequest
 */
trait AccountValidationRules
{
    protected function prepareForValidation(): void
    {
        $icon = $this->input('icon');

        $this->merge([
            'icon' => is_string($icon) && trim($icon) === '' ? null : $icon,
        ]);
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function accountRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'icon' => ['nullable', 'string', Rule::in(Icons::names())],
            'type' => ['required', Rule::enum(AccountType::class)],
            'currency' => ['required', Rule::enum(Currency::class)],
            'opening_balance' => ['required', 'regex:/^-?\d{1,13}(\.\d{1,2})?$/'],
            'opening_balance_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function accountMessages(): array
    {
        return [
            'name.required' => 'Enter a name.',
            'name.max' => 'Keep the name under 100 characters.',
            'icon.in' => 'Choose an icon from the list.',
            'type.required' => 'Choose a type.',
            'type.enum' => 'Choose a type.',
            'currency.required' => 'Choose RSD or EUR.',
            'currency.enum' => 'Choose RSD or EUR.',
            'opening_balance.required' => 'Enter an opening balance.',
            'opening_balance.regex' => 'Enter an amount with up to two decimal places.',
            'opening_balance_on.required' => 'Enter the date of the opening balance.',
            'opening_balance_on.date' => 'Enter the date of the opening balance.',
            'note.max' => 'Keep the note under 1000 characters.',
        ];
    }

    /**
     * @return array{
     *     name: string,
     *     icon: string|null,
     *     type: AccountType,
     *     currency: Currency,
     *     opening_balance: string,
     *     opening_balance_on: string,
     *     note: string|null
     * }
     */
    public function accountAttributes(): array
    {
        $note = trim($this->string('note')->toString());
        $icon = trim($this->string('icon')->toString());
        $openingBalanceOn = $this->date('opening_balance_on')?->toDateString();

        if ($openingBalanceOn === null) {
            throw ValidationException::withMessages([
                'opening_balance_on' => 'Enter the date of the opening balance.',
            ]);
        }

        return [
            'name' => trim($this->string('name')->toString()),
            'icon' => $icon === '' ? null : $icon,
            'type' => AccountType::from($this->string('type')->toString()),
            'currency' => Currency::from($this->string('currency')->toString()),
            'opening_balance' => Decimal::of($this->string('opening_balance')->toString())->toString(MoneyConverter::SCALE),
            'opening_balance_on' => $openingBalanceOn,
            'note' => $note === '' ? null : $note,
        ];
    }
}
