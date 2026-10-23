<?php

namespace App\Money;

use App\Contracts\ExchangeRate;
use App\Models\Workspace;
use Carbon\CarbonInterface;

class MoneyConverter
{
    public const int SCALE = 2;

    public function __construct(
        private ExchangeRate $exchangeRate,
        private MoneyFormatter $formatter,
    ) {}

    /**
     * Base-currency value to store for an amount, rounded to two decimals.
     */
    public function baseAmount(string $amount, string $currency, Workspace $workspace, ?CarbonInterface $on = null): string
    {
        $value = Decimal::of($amount);
        $rate = Decimal::of($this->exchangeRate->basePerUnit($workspace, $currency, $on));

        return $value->multiply($rate)->toString(self::SCALE);
    }

    /**
     * Show an already stored base amount in the chosen currency.
     * The base currency is returned unchanged. Anything else divides by its rate.
     */
    public function displayAmount(string $baseAmount, string $display, Workspace $workspace, ?CarbonInterface $on = null): string
    {
        $normalized = Decimal::of($baseAmount)->round(self::SCALE);

        if ($display === $workspace->base_currency) {
            return $normalized->toString(self::SCALE);
        }

        $rate = Decimal::of($this->exchangeRate->basePerUnit($workspace, $display, $on));

        return $normalized->divide($rate, self::SCALE)->toString(self::SCALE);
    }

    /**
     * Convert the base total, rather than adding already rounded display rows.
     *
     * @param  list<string>  $baseAmounts
     */
    public function displayTotal(array $baseAmounts, string $display, Workspace $workspace, ?CarbonInterface $on = null): string
    {
        $total = Decimal::of('0');

        foreach ($baseAmounts as $baseAmount) {
            $total = $total->add(Decimal::of($baseAmount)->round(self::SCALE));
        }

        return $this->displayAmount($total->toString(self::SCALE), $display, $workspace, $on);
    }

    /**
     * A figure for the screen. The original amount stays beside it when the
     * display currency is different from the currency it was entered in.
     */
    public function present(
        string $originalAmount,
        string $originalCurrency,
        string $baseAmount,
        string $display,
        Workspace $workspace,
        ?CarbonInterface $on = null,
    ): DisplayedMoney {
        $original = Decimal::of($originalAmount)->toString(self::SCALE);

        if ($display === $originalCurrency) {
            return new DisplayedMoney(
                amount: $original,
                currency: $display,
                formatted: $this->formatter->format($original, $display),
            );
        }

        $amount = $display === $workspace->base_currency
            ? Decimal::of($baseAmount)->toString(self::SCALE)
            : $this->displayAmount($baseAmount, $display, $workspace, $on);

        return new DisplayedMoney(
            amount: $amount,
            currency: $display,
            formatted: $this->formatter->format($amount, $display),
            originalAmount: $original,
            originalCurrency: $originalCurrency,
            originalFormatted: $this->formatter->format($original, $originalCurrency),
        );
    }
}
