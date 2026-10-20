<?php

namespace App\Http\Requests\Transactions;

use App\Concerns\TransactionValidationRules;
use App\Models\Transaction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTransactionRequest extends FormRequest
{
    use TransactionValidationRules;

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
        return $this->transactionRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->transactionMessages();
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        $transaction = $this->route('transaction');

        return $this->transactionChecks($transaction instanceof Transaction ? $transaction : null);
    }
}
