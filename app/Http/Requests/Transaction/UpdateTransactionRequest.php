<?php

declare(strict_types=1);

namespace App\Http\Requests\Transaction;

use App\Enums\TransactionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTransactionRequest extends FormRequest
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
            'account_id' => ['sometimes', 'required', 'exists:accounts,id'],
            'category_id' => [
                'nullable',
                'exists:categories,id',
            ],
            'type' => ['sometimes', 'required', 'string', Rule::enum(TransactionType::class)],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:1000'],
            'transaction_date' => ['sometimes', 'required', 'date'],
            'related_transaction_id' => ['nullable', 'exists:transactions,id'],
        ];
    }
}
