<?php

namespace App\Support;

use App\Contracts\ExchangeRate;
use App\Enums\Currency;
use App\Exceptions\MissingExchangeRate;
use App\Models\Account;
use App\Models\ExchangeRateQuote;
use App\Models\Transaction;
use App\Models\Workspace;
use App\Models\WorkspaceCurrency;

class CurrencyShare
{
    public function __construct(private ExchangeRate $exchangeRate) {}

    /**
     * @return array{
     *     base: string,
     *     supported: list<array{code: string, name: string, rateToBase: string|null}>
     * }
     */
    public function forRequest(?Workspace $workspace): array
    {
        if (! $workspace instanceof Workspace) {
            return [
                'base' => '',
                'supported' => [],
            ];
        }

        $workspace->loadMissing('currencies');

        return [
            'base' => $workspace->base_currency,
            'supported' => array_values($workspace->currencies
                ->sortBy(fn (WorkspaceCurrency $currency): array => [
                    $currency->code === $workspace->base_currency ? 0 : 1,
                    $currency->code,
                ])
                ->map(fn (WorkspaceCurrency $currency): array => [
                    'code' => $currency->code,
                    'name' => $this->name($currency->code),
                    'rateToBase' => $this->rate($workspace, $currency->code),
                ])
                ->all()),
        ];
    }

    /**
     * @return array{
     *     base: string,
     *     baseName: string,
     *     locked: bool,
     *     currencies: list<array{
     *         code: string,
     *         name: string,
     *         rateToBase: string|null,
     *         manualRate: string|null,
     *         source: string,
     *         quotedOn: string|null,
     *         removable: bool
     *     }>,
     *     catalog: list<array{code: string, name: string}>
     * }
     */
    public function settings(Workspace $workspace): array
    {
        $workspace->loadMissing('currencies');
        $used = Account::query()->whereBelongsTo($workspace)->distinct()->pluck('currency')
            ->merge(Transaction::query()->whereBelongsTo($workspace)->distinct()->pluck('currency'))
            ->unique();
        $enabled = $workspace->currencies->pluck('code');

        $catalog = [];

        foreach (Currency::cases() as $currency) {
            if ($enabled->contains($currency->value)) {
                continue;
            }

            $catalog[] = [
                'code' => $currency->value,
                'name' => $currency->label(),
            ];
        }

        return [
            'base' => $workspace->base_currency,
            'baseName' => $this->name($workspace->base_currency),
            'locked' => $workspace->hasLedger(),
            'currencies' => array_values($workspace->currencies
                ->sortBy(fn (WorkspaceCurrency $currency): array => [
                    $currency->code === $workspace->base_currency ? 0 : 1,
                    $currency->code,
                ])
                ->map(function (WorkspaceCurrency $currency) use ($workspace, $used): array {
                    $manual = $currency->manual_rate;

                    return [
                        'code' => $currency->code,
                        'name' => $this->name($currency->code),
                        'rateToBase' => $this->rate($workspace, $currency->code),
                        'manualRate' => is_string($manual) && $manual !== '' ? Rate::trim($manual) : null,
                        'source' => $this->source($workspace, $currency),
                        'quotedOn' => $this->quotedOn($currency->code === 'EUR' ? $workspace->base_currency : $currency->code),
                        'removable' => $currency->code !== $workspace->base_currency && ! $used->contains($currency->code),
                    ];
                })
                ->all()),
            'catalog' => $catalog,
        ];
    }

    private function name(string $code): string
    {
        return Currency::tryFrom($code)?->label() ?? $code;
    }

    private function source(Workspace $workspace, WorkspaceCurrency $currency): string
    {
        if ($currency->code === $workspace->base_currency) {
            return 'base';
        }

        if (is_string($currency->manual_rate) && $currency->manual_rate !== '') {
            return 'manual';
        }

        return 'frankfurter';
    }

    private function quotedOn(string $currency): ?string
    {
        if ($currency === Currency::Eur->value) {
            return null;
        }

        $quote = ExchangeRateQuote::query()
            ->where('currency', $currency)
            ->orderByDesc('quoted_on')
            ->first();

        return $quote?->quoted_on->toDateString();
    }

    private function rate(Workspace $workspace, string $code): ?string
    {
        try {
            return $this->exchangeRate->basePerUnit($workspace, $code);
        } catch (MissingExchangeRate) {
            return null;
        }
    }
}
