<?php

namespace App\Http\Requests\Categories;

use App\Concerns\CategoryValidationRules;
use App\Models\Category;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    use CategoryValidationRules;

    public function authorize(): bool
    {
        $category = $this->route('category');

        return $category instanceof Category
            && ($this->user()?->can('update', $category) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $category = $this->route('category');

        return $this->categoryRules($category instanceof Category ? $category : null);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->categoryMessages();
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $category = $this->route('category');

                if (! $category instanceof Category || $validator->errors()->has('kind')) {
                    return;
                }

                if (! $category->isInUse()) {
                    return;
                }

                if ($this->string('kind')->toString() === $category->kind->value) {
                    return;
                }

                $validator->errors()->add(
                    'kind',
                    'Kind cannot change once the category has history.',
                );
            },
        ];
    }
}
