<?php

namespace App\Services;

use App\Models\ExchangeRateQuote;
use Carbon\CarbonInterface;

class FetchExchangeRates
{
    public function __construct(private Frankfurter $frankfurter) {}

    /**
     * Store the latest rates, or the last 120 days when nothing recent is on file.
     */
    public function handle(): int
    {
        $recent = ExchangeRateQuote::query()
            ->whereDate('quoted_on', '>=', now()->subDays(14)->toDateString())
            ->exists();

        if ($recent) {
            return $this->store($this->frankfurter->latest());
        }

        return $this->between(now()->subDays(120), now());
    }

    public function between(CarbonInterface $from, CarbonInterface $to): int
    {
        return $this->store($this->frankfurter->between($from, $to));
    }

    /**
     * @param  list<array{quoted_on: string, currency: string, per_euro: string}>  $quotes
     */
    private function store(array $quotes): int
    {
        if ($quotes === []) {
            return 0;
        }

        $now = now()->toDateTimeString();
        $rows = array_map(fn (array $quote): array => [
            ...$quote,
            'created_at' => $now,
            'updated_at' => $now,
        ], $quotes);

        foreach (array_chunk($rows, 500) as $chunk) {
            ExchangeRateQuote::query()->upsert($chunk, ['quoted_on', 'currency'], ['per_euro', 'updated_at']);
        }

        return count($rows);
    }
}
