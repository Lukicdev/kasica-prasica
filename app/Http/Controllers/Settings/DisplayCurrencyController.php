<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Users\UpdateDisplayCurrency;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateDisplayCurrencyRequest;
use Illuminate\Http\RedirectResponse;

class DisplayCurrencyController extends Controller
{
    /**
     * Remember which currency this user wants figures shown in.
     */
    public function __invoke(UpdateDisplayCurrencyRequest $request, UpdateDisplayCurrency $update): RedirectResponse
    {
        $update->handle($request->user(), $request->currency());

        return back();
    }
}
