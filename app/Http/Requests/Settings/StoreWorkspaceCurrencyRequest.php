<?php

namespace App\Http\Requests\Settings;

use App\Enums\Currency;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkspaceCurrencyRequest extends FormRequest
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
            'code' => ['required', 'string', Rule::enum(Currency::class)],
            'manual_rate' => ['nullable', 'regex:/^\d{1,10}(\.\d{1,8})?$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Choose a currency.',
            'code.enum' => 'Choose a currency from the list.',
            'manual_rate.regex' => 'Enter a rate greater than zero.',
        ];
    }

    public function code(): string
    {
        return strtoupper($this->string('code')->toString());
    }

    public function manualRate(): ?string
    {
        $rate = trim($this->string('manual_rate')->toString());

        return $rate === '' ? null : $rate;
    }
}
