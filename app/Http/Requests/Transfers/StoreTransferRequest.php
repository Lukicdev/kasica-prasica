<?php

namespace App\Http\Requests\Transfers;

use App\Concerns\TransferValidationRules;
use App\Models\Transaction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTransferRequest extends FormRequest
{
    use TransferValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Transaction::class) ?? false;
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
        return $this->transferChecks(null);
    }
}
