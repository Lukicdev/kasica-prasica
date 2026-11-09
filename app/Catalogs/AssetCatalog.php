<?php

namespace App\Catalogs;

use App\Models\Asset;
use App\Models\Workspace;
use App\Money\Decimal;
use App\Money\MoneyConverter;
use App\Money\MoneyFormatter;
use Illuminate\Support\Collection;

/**
 * The asset page: each value in that asset's currency, and one total in the
 * display currency. The total is converted from the base-currency sum.
 * Values are the amounts entered by hand.
 */
class AssetCatalog
{
    public function __construct(
        private MoneyConverter $converter,
        private MoneyFormatter $formatter,
    ) {}

    /**
     * @param  Collection<int, Asset>  $assets
     * @return array{
     *     groups: list<array{
     *         type: string,
     *         label: string,
     *         assets: list<array{
     *             id: int,
     *             name: string,
     *             icon: string|null,
     *             type: string,
     *             type_label: string,
     *             currency: string,
     *             current_value: array{amount: string, currency: string, formatted: string, negative: bool},
     *             display_value: array{amount: string, currency: string, formatted: string, negative: bool}|null,
     *             valued_on_label: string,
     *             valuation_label: string,
     *             archived: bool,
     *             note: string|null
     *         }>
     *     }>,
     *     total: array{amount: string, currency: string, formatted: string, negative: bool}
     * }
     */
    public function list(Collection $assets, string $display, Workspace $workspace): array
    {
        $grouped = [];

        $sorted = $assets->sortBy(fn (Asset $asset): array => [
            $asset->type->order(),
            $asset->archived_at === null ? 0 : 1,
            mb_strtolower($asset->name),
        ])->values();

        foreach ($sorted as $asset) {
            $type = $asset->type->value;

            if (! isset($grouped[$type])) {
                $grouped[$type] = [
                    'type' => $type,
                    'label' => $asset->type->label(),
                    'assets' => [],
                ];
            }

            $grouped[$type]['assets'][] = $this->summary($asset, $display, $workspace);
        }

        return [
            'groups' => array_values($grouped),
            'total' => $this->money($this->displayTotal($assets, $display, $workspace), $display),
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     icon: string|null,
     *     type: string,
     *     type_label: string,
     *     currency: string,
     *     value: string,
     *     valued_on: string,
     *     valued_on_label: string,
     *     valuation: string,
     *     valuation_label: string,
     *     note: string|null,
     *     archived: bool,
     *     current_value: array{amount: string, currency: string, formatted: string, negative: bool},
     *     display_value: array{amount: string, currency: string, formatted: string, negative: bool}|null,
     *     updated_by: string|null
     * }
     */
    public function detail(Asset $asset, string $display, Workspace $workspace): array
    {
        $asset->loadMissing(['editor:id,name']);
        $editor = $asset->editor;
        $updatedBy = null;

        if (
            $editor !== null
            && $asset->updated_at !== null
            && $asset->created_at !== null
            && $asset->updated_at->greaterThan($asset->created_at)
        ) {
            $updatedBy = $editor->name;
        }

        $value = Decimal::of((string) $asset->value)->toString(MoneyConverter::SCALE);

        return [
            ...$this->form($asset),
            'type_label' => $asset->type->label(),
            'valued_on_label' => $asset->valued_on->format('j M Y'),
            'valuation_label' => $asset->valuation->label(),
            'current_value' => $this->money($value, $asset->currency),
            'display_value' => $this->displayValue($asset, $value, $display, $workspace),
            'updated_by' => $updatedBy,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     icon: string|null,
     *     type: string,
     *     currency: string,
     *     value: string,
     *     valued_on: string,
     *     valuation: string,
     *     note: string|null,
     *     archived: bool
     * }
     */
    public function form(Asset $asset): array
    {
        return [
            'id' => $asset->id,
            'name' => $asset->name,
            'icon' => $asset->icon,
            'type' => $asset->type->value,
            'currency' => $asset->currency,
            'value' => Decimal::of((string) $asset->value)->toString(MoneyConverter::SCALE),
            'valued_on' => $asset->valued_on->toDateString(),
            'valuation' => $asset->valuation->value,
            'note' => $asset->note,
            'archived' => $asset->archived_at !== null,
        ];
    }

    /**
     * @param  Collection<int, Asset>  $assets
     */
    public function displayTotal(Collection $assets, string $display, Workspace $workspace): string
    {
        $total = Decimal::of('0');

        foreach ($assets as $asset) {
            $value = Decimal::of((string) $asset->value)->toString(MoneyConverter::SCALE);
            $base = $asset->currency === $workspace->base_currency
                ? $value
                : $this->converter->baseAmount($value, $asset->currency, $workspace);

            $total = $total->add(Decimal::of($base));
        }

        return $this->converter->displayAmount($total->toString(MoneyConverter::SCALE), $display, $workspace);
    }

    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     icon: string|null,
     *     type: string,
     *     type_label: string,
     *     currency: string,
     *     current_value: array{amount: string, currency: string, formatted: string, negative: bool},
     *     display_value: array{amount: string, currency: string, formatted: string, negative: bool}|null,
     *     valued_on_label: string,
     *     valuation_label: string,
     *     archived: bool,
     *     note: string|null
     * }
     */
    private function summary(Asset $asset, string $display, Workspace $workspace): array
    {
        $value = Decimal::of((string) $asset->value)->toString(MoneyConverter::SCALE);

        return [
            'id' => $asset->id,
            'name' => $asset->name,
            'icon' => $asset->icon,
            'type' => $asset->type->value,
            'type_label' => $asset->type->label(),
            'currency' => $asset->currency,
            'current_value' => $this->money($value, $asset->currency),
            'display_value' => $this->displayValue($asset, $value, $display, $workspace),
            'valued_on_label' => $asset->valued_on->format('j M Y'),
            'valuation_label' => $asset->valuation->label(),
            'archived' => $asset->archived_at !== null,
            'note' => $asset->note,
        ];
    }

    /**
     * The same value in the display currency, when that currency differs.
     *
     * @return array{amount: string, currency: string, formatted: string, negative: bool}|null
     */
    private function displayValue(Asset $asset, string $value, string $display, Workspace $workspace): ?array
    {
        if ($asset->currency === $display) {
            return null;
        }

        $base = $asset->currency === $workspace->base_currency
            ? $value
            : $this->converter->baseAmount($value, $asset->currency, $workspace);

        return $this->money($this->converter->displayAmount($base, $display, $workspace), $display);
    }

    /**
     * @return array{amount: string, currency: string, formatted: string, negative: bool}
     */
    private function money(string $amount, string $currency): array
    {
        $normalized = Decimal::of($amount)->toString(MoneyConverter::SCALE);

        return [
            'amount' => $normalized,
            'currency' => $currency,
            'formatted' => $this->formatter->format($normalized, $currency),
            'negative' => Decimal::of($normalized)->isNegative(),
        ];
    }
}
