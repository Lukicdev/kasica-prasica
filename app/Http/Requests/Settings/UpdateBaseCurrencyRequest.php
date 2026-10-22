<?php

namespace App\Http\Requests\Settings;

use App\Enums\Currency;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBaseCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'currency' => ['required', 'string', Rule::enum(Currency::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'currency.required' => 'Choose a currency.',
            'currency.enum' => 'Choose a currency from the list.',
        ];
    }

    public function currency(): string
    {
        return strtoupper($this->string('currency')->toString());
    }
}
