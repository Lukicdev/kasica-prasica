<?php

namespace App\Http\Requests\Assets;

use App\Concerns\AssetValidationRules;
use App\Models\Asset;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAssetRequest extends FormRequest
{
    use AssetValidationRules;

    public function authorize(): bool
    {
        $asset = $this->route('asset');

        return $asset instanceof Asset
            && ($this->user()?->can('update', $asset) ?? false);
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
