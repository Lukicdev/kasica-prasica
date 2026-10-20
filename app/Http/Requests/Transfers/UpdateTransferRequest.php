<?php

namespace App\Http\Requests\Transfers;

use App\Concerns\TransferValidationRules;
use App\Enums\TransactionType;
use App\Models\Transaction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateTransferRequest extends FormRequest
{
    use TransferValidationRules;

    public function authorize(): bool
    {
        $transaction = $this->route('transaction');

        return $transaction instanceof Transaction
            && $transaction->type === TransactionType::Transfer
            && ($this->user()?->can('update', $transaction) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->transferRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->transferMessages();
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        $transaction = $this->route('transaction');

        return $this->transferChecks($transaction instanceof Transaction ? $transaction : null);
    }
}
