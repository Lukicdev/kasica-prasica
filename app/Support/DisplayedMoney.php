<?php

namespace App\Support;

final readonly class DisplayedMoney
{
    public function __construct(
        public string $amount,
        public string $currency,
        public string $formatted,
        public ?string $originalAmount = null,
        public ?string $originalCurrency = null,
        public ?string $originalFormatted = null,
    ) {}

    /**
     * @return array{
     *     amount: string,
     *     currency: string,
     *     formatted: string,
     *     original_amount: string|null,
     *     original_currency: string|null,
     *     original_formatted: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'formatted' => $this->formatted,
            'original_amount' => $this->originalAmount,
            'original_currency' => $this->originalCurrency,
            'original_formatted' => $this->originalFormatted,
        ];
    }
}
