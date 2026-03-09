<?php

declare(strict_types=1);

namespace App\Http\Requests\Budget;

use App\Enums\CategoryType;
use App\Models\Budget;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateBudgetRequest extends FormRequest
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
        $user = $this->user();
        $budget = $this->route('budget');

        return [
            'category_id' => [
                'sometimes',
                'required',
                Rule::exists('categories', 'id')
                    ->where('type', CategoryType::Expense)
                    ->where(fn ($query) => $query->where('user_id', $user->id)->orWhereNull('user_id')),
            ],
            'amount' => ['sometimes', 'required', 'numeric', 'min:0'],
            'period' => [
                'sometimes',
                'required',
                'date',
                function (string $attribute, mixed $value, Closure $fail) use ($budget): void {
                    $exists = Budget::query()
                        ->where('user_id', $this->user()->id)
                        ->where('category_id', (int) $this->input('category_id', $budget->category_id))
                        ->whereDate('period', $value)
                        ->where('id', '!=', $budget->id)
                        ->exists();
                    if ($exists) {
                        $fail('A budget already exists for this category and month.');
                    }
                },
            ],
        ];
    }
}
