<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkspaceCurrencyRequest extends FormRequest
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
            'manual_rate' => ['nullable', 'regex:/^\d{1,10}(\.\d{1,8})?$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'manual_rate.regex' => 'Enter a rate greater than zero.',
        ];
    }

    public function manualRate(): ?string
    {
        $rate = trim($this->string('manual_rate')->toString());

        return $rate === '' ? null : $rate;
    }
}
