<?php

namespace App\Http\Requests\Accounts;

use App\Concerns\AccountValidationRules;
use App\Models\Account;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRequest extends FormRequest
{
    use AccountValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Account::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->accountRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->accountMessages();
    }
}
