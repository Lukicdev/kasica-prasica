<?php

namespace App\Console\Commands;

use App\Services\FetchExchangeRates;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Throwable;

class FetchExchangeRatesCommand extends Command
{
    protected $signature = 'exchange-rates:fetch {--from=} {--to=}';

    protected $description = 'Download daily exchange rates from Frankfurter';

    public function handle(FetchExchangeRates $fetch): int
    {
        try {
            $from = $this->option('from');
            $to = $this->option('to');

            $count = is_string($from) && $from !== ''
                ? $fetch->between(
                    CarbonImmutable::parse($from),
                    CarbonImmutable::parse(is_string($to) && $to !== '' ? $to : now()->toDateString()),
                )
                : $fetch->handle();
        } catch (ConnectionException|RequestException $exception) {
            report($exception);
            $this->components->error('Frankfurter could not be reached. Stored rates were left unchanged.');

            return self::FAILURE;
        } catch (Throwable $exception) {
            report($exception);
            $this->components->error('Exchange rates could not be saved. Stored rates were left unchanged.');

            return self::FAILURE;
        }

        $this->components->info("Saved {$count} exchange rates.");

        return self::SUCCESS;
    }
}
