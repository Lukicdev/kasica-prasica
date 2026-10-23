<?php

namespace App\Http\Controllers;

use App\Actions\Accounts\ArchiveAccount;
use App\Actions\Accounts\CreateAccount;
use App\Actions\Accounts\DeleteAccount;
use App\Actions\Accounts\RestoreAccount;
use App\Actions\Accounts\UpdateAccount;
use App\Catalogs\AccountCatalog;
use App\Catalogs\AccountStatement;
use App\Enums\AccountType;
use App\Http\Requests\Accounts\StoreAccountRequest;
use App\Http\Requests\Accounts\UpdateAccountRequest;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function __construct(
        private AccountCatalog $catalog,
        private AccountStatement $statement,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        Gate::authorize('viewAny', Account::class);

        $accounts = Account::query()
            ->whereBelongsTo($user->workspace)
            ->get();

        return Inertia::render('accounts/index', $this->catalog->list(
            $accounts,
            $user->display_currency,
            $user->workspace,
        ));
    }

    public function create(): Response
    {
        Gate::authorize('create', Account::class);

        return Inertia::render('accounts/create', [
            'types' => AccountType::options(),
            'today' => now()->toDateString(),
        ]);
    }

    public function store(StoreAccountRequest $request, CreateAccount $create): RedirectResponse
    {
        $user = $request->user();
        $create->handle($user, $request->accountAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Account created.']);

        return to_route('accounts.index');
    }

    public function show(Request $request, Account $account): Response
    {
        Gate::authorize('view', $account);

        $page = $request->integer('page');

        return Inertia::render('accounts/show', [
            'account' => $this->catalog->detail($account),
            ...$this->statement->lines($account, $page > 0 ? $page : 1),
        ]);
    }

    public function edit(Account $account): Response
    {
        Gate::authorize('update', $account);

        return Inertia::render('accounts/edit', [
            'account' => $this->catalog->form($account),
            'types' => AccountType::options(),
        ]);
    }

    public function update(UpdateAccountRequest $request, Account $account, UpdateAccount $update): RedirectResponse
    {
        $user = $request->user();

        $update->handle($account, $user, $request->accountAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Account updated.']);

        return to_route('accounts.show', $account);
    }

    public function destroy(Request $request, Account $account, DeleteAccount $delete): RedirectResponse
    {
        Gate::authorize('delete', $account);

        $delete->handle($account, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Account deleted.']);

        return to_route('accounts.index');
    }

    public function archive(Request $request, Account $account, ArchiveAccount $archive): RedirectResponse
    {
        Gate::authorize('update', $account);

        $archive->handle($account, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Account archived.']);

        return to_route('accounts.show', $account);
    }

    public function restore(Request $request, Account $account, RestoreAccount $restore): RedirectResponse
    {
        Gate::authorize('update', $account);

        $restore->handle($account, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Account restored.']);

        return to_route('accounts.show', $account);
    }
}
