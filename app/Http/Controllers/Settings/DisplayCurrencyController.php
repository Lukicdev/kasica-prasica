<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateDisplayCurrencyRequest;
use Illuminate\Http\RedirectResponse;

class DisplayCurrencyController extends Controller
{
    /**
     * Remember which currency this user wants figures shown in.
     */
    public function __invoke(UpdateDisplayCurrencyRequest $request): RedirectResponse
    {
        $request->user()->update([
            'display_currency' => $request->currency(),
        ]);

        return back();
    }
}
