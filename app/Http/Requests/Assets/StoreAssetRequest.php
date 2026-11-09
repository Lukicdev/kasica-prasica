<?php

namespace App\Http\Requests\Assets;

use App\Concerns\AssetValidationRules;
use App\Models\Asset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAssetRequest extends FormRequest
{
    use AssetValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Asset::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->assetRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->assetMessages();
    }
}
