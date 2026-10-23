<?php

namespace App\Http\Controllers\Settings;

use App\Actions\Currencies\AddWorkspaceCurrency;
use App\Actions\Currencies\ChangeBaseCurrency;
use App\Actions\Currencies\RemoveWorkspaceCurrency;
use App\Actions\Currencies\UpdateWorkspaceCurrency;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreWorkspaceCurrencyRequest;
use App\Http\Requests\Settings\UpdateBaseCurrencyRequest;
use App\Http\Requests\Settings\UpdateWorkspaceCurrencyRequest;
use App\Models\WorkspaceCurrency;
use App\Money\CurrencyShare;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CurrencyController extends Controller
{
    public function edit(Request $request, CurrencyShare $currencies): Response
    {
        return Inertia::render('settings/currencies', [
            'currencySettings' => $currencies->settings($request->user()->workspace),
        ]);
    }

    public function store(StoreWorkspaceCurrencyRequest $request, AddWorkspaceCurrency $add): RedirectResponse
    {
        $add->handle($request->user()->workspace, $request->code(), $request->manualRate());

        return back();
    }

    public function update(
        UpdateWorkspaceCurrencyRequest $request,
        string $code,
        UpdateWorkspaceCurrency $update,
    ): RedirectResponse {
        $update->handle($this->currency($request, $code), $request->manualRate());

        return back();
    }

    public function destroy(Request $request, string $code, RemoveWorkspaceCurrency $remove): RedirectResponse
    {
        $remove->handle($this->currency($request, $code));

        return back();
    }

    public function updateBase(UpdateBaseCurrencyRequest $request, ChangeBaseCurrency $change): RedirectResponse
    {
        $change->handle($request->user()->workspace, $request->currency());

        return back();
    }

    private function currency(Request $request, string $code): WorkspaceCurrency
    {
        return $request->user()->workspace->currencies()
            ->where('code', strtoupper($code))
            ->firstOrFail();
    }
}
