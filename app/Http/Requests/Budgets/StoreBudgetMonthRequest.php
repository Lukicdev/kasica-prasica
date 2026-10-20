<?php

namespace App\Http\Requests\Budgets;

use App\Models\BudgetMonth;
use App\Support\CalendarMonth;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBudgetMonthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', BudgetMonth::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'copy' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'month.required' => 'Choose a month.',
            'month.regex' => 'Choose a month.',
            'copy.required' => 'Choose how to start the month.',
            'copy.boolean' => 'Choose how to start the month.',
        ];
    }

    public function month(): CalendarMonth
    {
        return CalendarMonth::fromValue($this->string('month')->toString());
    }
}
