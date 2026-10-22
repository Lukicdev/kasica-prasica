<?php

namespace App\Http\Requests\Budgets;

use App\Enums\CategoryKind;
use App\Models\BudgetMonth;
use App\Models\Category;
use App\Support\CalendarMonth;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryBudgetRequest extends FormRequest
{
    private ?Category $resolvedCategory = null;

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
            'category_id' => ['required', 'integer'],
            'amount' => ['required', 'regex:/^\d{1,13}(\.\d{1,2})?$/'],
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
            'category_id.required' => 'Choose an expense category.',
            'category_id.integer' => 'Choose an expense category.',
            'amount.required' => 'Enter an amount.',
            'amount.regex' => 'Enter an amount with up to two decimal places.',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $user = $this->user();

                if ($user === null) {
                    return;
                }

                if ($user->display_currency !== $user->workspace->base_currency) {
                    $validator->errors()->add('amount', "Switch to {$user->workspace->base_currency} to edit the budget.");
                }

                if ($validator->errors()->has('category_id')) {
                    return;
                }

                $category = Category::query()
                    ->whereBelongsTo($user->workspace)
                    ->whereKey($this->integer('category_id'))
                    ->first();

                if (! $category instanceof Category || $category->kind !== CategoryKind::Expense) {
                    $validator->errors()->add('category_id', 'Choose an expense category.');

                    return;
                }

                $this->resolvedCategory = $category;
            },
        ];
    }

    public function month(): CalendarMonth
    {
        return CalendarMonth::fromValue($this->string('month')->toString());
    }

    public function category(): Category
    {
        if ($this->resolvedCategory instanceof Category) {
            return $this->resolvedCategory;
        }

        $category = Category::query()->whereKey($this->integer('category_id'))->first();

        if (! $category instanceof Category) {
            abort(404);
        }

        return $category;
    }

    public function amount(): string
    {
        return $this->string('amount')->toString();
    }
}
