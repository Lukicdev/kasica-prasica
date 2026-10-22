<?php

namespace App\Concerns;

use App\Enums\CategoryKind;
use App\Models\Category;
use App\Support\CategoryColors;
use App\Support\Icons;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @phpstan-require-extends FormRequest
 */
trait CategoryValidationRules
{
    protected function prepareForValidation(): void
    {
        $description = $this->input('description');
        $icon = $this->input('icon');
        $color = $this->input('color');

        $this->merge([
            'name' => trim($this->string('name')->toString()),
            'description' => is_string($description) ? trim($description) : $description,
            'icon' => is_string($icon) && trim($icon) === '' ? null : $icon,
            'color' => is_string($color) && trim($color) === '' ? null : $color,
        ]);
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function categoryRules(?Category $category = null): array
    {
        $workspaceId = $this->user()?->workspace_id;
        $kind = $this->string('kind')->toString();

        $unique = Rule::unique((new Category)->getTable(), 'name')
            ->where(fn ($query) => $query
                ->where('workspace_id', $workspaceId)
                ->where('kind', $kind));

        if ($category !== null) {
            $unique->ignore($category);
        }

        return [
            'name' => ['required', 'string', 'max:100', $unique],
            'icon' => ['nullable', 'string', Rule::in(Icons::names())],
            'color' => ['nullable', 'string', Rule::in(CategoryColors::names())],
            'kind' => ['required', Rule::enum(CategoryKind::class)],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function categoryMessages(): array
    {
        return [
            'name.required' => 'Enter a name.',
            'name.max' => 'Keep the name under 100 characters.',
            'name.unique' => 'A category with this name already exists.',
            'icon.in' => 'Choose an icon from the list.',
            'color.in' => 'Choose a color from the list.',
            'kind.required' => 'Choose income or expense.',
            'kind.enum' => 'Choose income or expense.',
            'description.max' => 'Keep the description under 255 characters.',
        ];
    }

    /**
     * @return array{name: string, icon: string|null, color: string|null, kind: CategoryKind, description: string|null}
     */
    public function categoryAttributes(): array
    {
        $description = trim($this->string('description')->toString());
        $icon = trim($this->string('icon')->toString());
        $color = trim($this->string('color')->toString());

        return [
            'name' => trim($this->string('name')->toString()),
            'icon' => $icon === '' ? null : $icon,
            'color' => $color === '' ? null : $color,
            'kind' => CategoryKind::from($this->string('kind')->toString()),
            'description' => $description === '' ? null : $description,
        ];
    }
}
