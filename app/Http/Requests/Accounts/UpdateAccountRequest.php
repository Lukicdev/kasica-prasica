<?php

namespace App\Http\Requests\Accounts;

use App\Concerns\AccountValidationRules;
use App\Models\Account;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    use AccountValidationRules;

    public function authorize(): bool
    {
        $account = $this->route('account');

        return $account instanceof Account
            && ($this->user()?->can('update', $account) ?? false);
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

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $account = $this->route('account');

                if (! $account instanceof Account || $validator->errors()->has('currency')) {
                    return;
                }

                if (! $account->transactions()->exists()) {
                    return;
                }

                if ($this->string('currency')->toString() === $account->currency->value) {
                    return;
                }

                $validator->errors()->add(
                    'currency',
                    'Currency cannot change once the account has transactions.',
                );
            },
        ];
    }
}
