<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\FetchExchangeRates;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;

class ExchangeRateController extends Controller
{
    public function __invoke(FetchExchangeRates $fetch): RedirectResponse
    {
        try {
            $fetch->handle();
        } catch (ConnectionException|RequestException $exception) {
            report($exception);

            return back()->withErrors([
                'rate' => 'Frankfurter could not be reached. Stored rates were left unchanged.',
            ]);
        }

        return back();
    }
}
