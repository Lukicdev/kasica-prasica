<?php

namespace App\Concerns;

use App\Enums\AssetType;
use App\Enums\Icon;
use App\Money\Decimal;
use App\Money\MoneyConverter;
use App\Rules\EnabledCurrency;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * @phpstan-require-extends FormRequest
 */
trait AssetValidationRules
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
    protected function assetRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'icon' => ['nullable', Rule::enum(Icon::class)],
            'type' => ['required', Rule::enum(AssetType::class)],
            'currency' => ['required', 'string', 'size:3', new EnabledCurrency($this->user())],
            'value' => ['required', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
            'valued_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function assetMessages(): array
    {
        return [
            'name.required' => 'Enter a name.',
            'name.max' => 'Keep the name under 100 characters.',
            'icon.enum' => 'Choose an icon from the list.',
            'type.required' => 'Choose a type.',
            'type.enum' => 'Choose a type.',
            'currency.required' => 'Choose a currency.',
            'currency.size' => 'Choose a currency.',
            'value.required' => 'Enter a value.',
            'value.regex' => 'Enter a value of zero or more, with up to two decimal places.',
            'valued_on.required' => 'Enter the date of the value.',
            'valued_on.date' => 'Enter the date of the value.',
            'note.max' => 'Keep the note under 1000 characters.',
        ];
    }

    /**
     * @return array{
     *     name: string,
     *     icon: string|null,
     *     type: AssetType,
     *     currency: string,
     *     value: string,
     *     valued_on: string,
     *     note: string|null
     * }
     */
    public function assetAttributes(): array
    {
        $note = trim($this->string('note')->toString());
        $icon = trim($this->string('icon')->toString());
        $valuedOn = $this->date('valued_on')?->toDateString();

        if ($valuedOn === null) {
            throw ValidationException::withMessages([
                'valued_on' => 'Enter the date of the value.',
            ]);
        }

        return [
            'name' => trim($this->string('name')->toString()),
            'icon' => $icon === '' ? null : $icon,
            'type' => AssetType::from($this->string('type')->toString()),
            'currency' => strtoupper($this->string('currency')->toString()),
            'value' => Decimal::of($this->string('value')->toString())->toString(MoneyConverter::SCALE),
            'valued_on' => $valuedOn,
            'note' => $note === '' ? null : $note,
        ];
    }
}
