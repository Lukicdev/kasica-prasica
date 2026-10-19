<?php

namespace App\Support;

use App\Contracts\ExchangeRate;
use App\Enums\Currency;
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
    public function baseAmount(string $amount, Currency $currency, ?CarbonInterface $on = null): string
    {
        $value = Decimal::of($amount);
        $rate = Decimal::of($this->exchangeRate->rsdPerUnit($currency, $on));

        return $value->multiply($rate)->toString(self::SCALE);
    }

    /**
     * Show an already stored dinar amount in the chosen currency.
     * Dinars are returned unchanged. Euros are the dinar amount divided by the rate.
     */
    public function displayAmount(string $baseAmount, Currency $display, ?CarbonInterface $on = null): string
    {
        $normalized = Decimal::of($baseAmount)->round(self::SCALE);

        if ($display->isBase()) {
            return $normalized->toString(self::SCALE);
        }

        $rate = Decimal::of($this->exchangeRate->rsdPerUnit($display, $on));

        return $normalized->divide($rate, self::SCALE)->toString(self::SCALE);
    }

    /**
     * Convert the dinar total, rather than adding already rounded display rows.
     *
     * @param  list<string>  $baseAmounts
     */
    public function displayTotal(array $baseAmounts, Currency $display, ?CarbonInterface $on = null): string
    {
        $total = Decimal::of('0');

        foreach ($baseAmounts as $baseAmount) {
            $total = $total->add(Decimal::of($baseAmount)->round(self::SCALE));
        }

        return $this->displayAmount($total->toString(self::SCALE), $display, $on);
    }

    /**
     * A figure for the screen. The original amount stays beside it when the
     * display currency is different from the currency it was entered in.
     */
    public function present(
        string $originalAmount,
        Currency $originalCurrency,
        string $baseAmount,
        Currency $display,
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

        $amount = $display->isBase()
            ? Decimal::of($baseAmount)->toString(self::SCALE)
            : $this->displayAmount($baseAmount, $display, $on);

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
