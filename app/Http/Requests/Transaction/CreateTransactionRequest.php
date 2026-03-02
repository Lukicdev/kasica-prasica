<?php

declare(strict_types=1);

namespace App\Http\Requests\Transaction;

use App\Enums\TransactionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'account_id' => ['required', 'exists:accounts,id'],
            'category_id' => [
                'nullable',
                'exists:categories,id',
                Rule::requiredIf(fn () => ! in_array($this->input('type'), [TransactionType::Transfer->value])),
            ],
            'type' => ['required', 'string', Rule::enum(TransactionType::class)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:1000'],
            'transaction_date' => ['required', 'date'],
            'related_transaction_id' => [
                'nullable',
                'exists:transactions,id',
                Rule::requiredIf(fn () => $this->input('type') === TransactionType::Transfer->value),
            ],
        ];
    }
}
