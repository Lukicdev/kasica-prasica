<?php

namespace App\Services;

use App\Enums\Currency;
use App\Money\Rate;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Daily mid-market rates from Frankfurter, quoted as units of each currency per one euro.
 */
class Frankfurter
{
    /**
     * @return list<array{quoted_on: string, currency: string, per_euro: string}>
     */
    public function latest(): array
    {
        return $this->quotes([]);
    }

    /**
     * @return list<array{quoted_on: string, currency: string, per_euro: string}>
     */
    public function between(CarbonInterface $from, CarbonInterface $to): array
    {
        return $this->quotes([
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    /**
     * @param  array<string, string>  $query
     * @return list<array{quoted_on: string, currency: string, per_euro: string}>
     */
    private function quotes(array $query): array
    {
        $response = Http::baseUrl(rtrim((string) config('services.frankfurter.url'), '/'))
            ->acceptJson()
            ->timeout(10)
            ->connectTimeout(3)
            ->retry(3, 200, function (mixed $exception): bool {
                return $exception instanceof ConnectionException
                    || ($exception instanceof RequestException && $exception->response->serverError());
            })
            ->get('/rates', ['base' => 'eur', ...$query])
            ->throw();

        $rows = [];

        foreach ($response->json() ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $date = $row['date'] ?? null;
            $quote = $row['quote'] ?? null;
            $rate = $row['rate'] ?? null;

            if (! is_string($date) || ! is_string($quote) || (! is_int($rate) && ! is_float($rate) && ! is_string($rate))) {
                continue;
            }

            $currency = Currency::tryFrom(strtoupper($quote));

            if (! $currency instanceof Currency || $currency === Currency::Eur) {
                continue;
            }

            $rows[] = [
                'quoted_on' => $date,
                'currency' => $currency->value,
                'per_euro' => $this->decimal($rate),
            ];
        }

        return $rows;
    }

    private function decimal(int|float|string $rate): string
    {
        if (is_string($rate)) {
            return Rate::trim($rate);
        }

        return Rate::trim(sprintf('%.8F', $rate));
    }
}
