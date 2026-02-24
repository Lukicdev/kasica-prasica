<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CreateAccountAction;
use App\Actions\DeleteAccountAction;
use App\Actions\UpdateAccountAction;
use App\Http\Requests\CreateAccountRequest;
use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AccountController extends Controller
{
    /**
     * Display a listing of accounts.
     */
    public function index(Request $request): Response
    {
        $accounts = $request->user()->accounts()->latest()->get();

        return Inertia::render('accounts/index', [
            'accounts' => $accounts,
        ]);
    }

    /**
     * Show the form for creating a new account.
     */
    public function create(): Response
    {
        return Inertia::render('accounts/create');
    }

    /**
     * Store a newly created account.
     */
    public function store(CreateAccountRequest $request, CreateAccountAction $action): RedirectResponse
    {
        $action->handle($request->user(), $request->validated());

        return to_route('accounts.index');
    }

    /**
     * Display the specified account.
     */
    public function show(Account $account): Response
    {
        return Inertia::render('accounts/show', [
            'account' => $account->load('user'),
        ]);
    }

    /**
     * Show the form for editing the specified account.
     */
    public function edit(Account $account): Response
    {
        return Inertia::render('accounts/edit', [
            'account' => $account,
        ]);
    }

    /**
     * Update the specified account.
     */
    public function update(UpdateAccountRequest $request, Account $account, UpdateAccountAction $action): RedirectResponse
    {
        $action->handle($account, $request->validated());

        return to_route('accounts.index');
    }

    /**
     * Remove the specified account.
     */
    public function destroy(DeleteAccountRequest $request, Account $account, DeleteAccountAction $action): RedirectResponse
    {
        $action->handle($account);

        return to_route('accounts.index');
    }
}
