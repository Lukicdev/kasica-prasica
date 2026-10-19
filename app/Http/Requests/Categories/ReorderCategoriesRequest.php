<?php

namespace App\Http\Requests\Categories;

use App\Enums\CategoryKind;
use App\Models\Category;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderCategoriesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kind' => ['required', Rule::enum(CategoryKind::class)],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kind.required' => 'Choose income or expense.',
            'kind.enum' => 'Choose income or expense.',
            'ids.required' => 'Reorder the categories in this list.',
            'ids.min' => 'Reorder the categories in this list.',
            'ids.*.integer' => 'Reorder the categories in this list.',
            'ids.*.distinct' => 'Reorder the categories in this list.',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $user = $this->user();

                if (! $user instanceof User) {
                    return;
                }

                $kind = CategoryKind::from($this->string('kind')->toString());
                $expected = [];

                foreach (Category::query()
                    ->whereBelongsTo($user->workspace)
                    ->where('kind', $kind)
                    ->selectable()
                    ->pluck('id') as $id) {
                    $expected[] = (int) $id;
                }

                $given = [];

                foreach ($this->array('ids') as $id) {
                    $given[] = (int) $id;
                }

                $expectedSorted = $expected;
                $givenSorted = $given;
                sort($expectedSorted);
                sort($givenSorted);

                if ($givenSorted !== $expectedSorted) {
                    $validator->errors()->add('ids', 'Reorder the categories in this list.');
                }
            },
        ];
    }

    public function kind(): CategoryKind
    {
        return CategoryKind::from($this->string('kind')->toString());
    }

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        $ids = [];

        foreach ($this->array('ids') as $id) {
            $ids[] = (int) $id;
        }

        return $ids;
    }
}
