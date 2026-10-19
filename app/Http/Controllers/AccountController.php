<?php

namespace App\Http\Controllers;

use App\Actions\Accounts\CreateAccount;
use App\Actions\Accounts\UpdateAccount;
use App\Enums\AccountType;
use App\Http\Requests\Accounts\StoreAccountRequest;
use App\Http\Requests\Accounts\UpdateAccountRequest;
use App\Models\Account;
use App\Models\User;
use App\Support\AccountCatalog;
use App\Support\AccountStatement;
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
        $user = $this->user($request);

        Gate::authorize('viewAny', Account::class);

        $accounts = Account::query()
            ->listed()
            ->whereBelongsTo($user->workspace)
            ->get();

        return Inertia::render('accounts/index', $this->catalog->list(
            $accounts,
            $user->display_currency,
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
        $account = $create->handle($this->user($request), $request->accountAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Account created.']);

        return to_route('accounts.show', $account);
    }

    public function show(Account $account): Response
    {
        $this->ensureListed($account);
        Gate::authorize('view', $account);

        return Inertia::render('accounts/show', [
            'account' => $this->catalog->detail($account),
            'lines' => $this->statement->lines($account),
        ]);
    }

    public function edit(Account $account): Response
    {
        $this->ensureListed($account);
        Gate::authorize('update', $account);

        return Inertia::render('accounts/edit', [
            'account' => $this->catalog->form($account),
            'types' => AccountType::options(),
        ]);
    }

    public function update(UpdateAccountRequest $request, Account $account, UpdateAccount $update): RedirectResponse
    {
        $this->ensureListed($account);

        $update->handle($account, $this->user($request), $request->accountAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Account updated.']);

        return to_route('accounts.show', $account);
    }

    public function destroy(Account $account): RedirectResponse
    {
        $this->ensureListed($account);
        Gate::authorize('delete', $account);

        $account->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Account deleted.']);

        return to_route('accounts.index');
    }

    public function archive(Request $request, Account $account): RedirectResponse
    {
        $this->ensureListed($account);
        Gate::authorize('update', $account);

        if ($account->archived_at === null) {
            $account->update([
                'archived_at' => now(),
                'updated_by' => $this->user($request)->id,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Account archived.']);

        return to_route('accounts.show', $account);
    }

    public function restore(Request $request, Account $account): RedirectResponse
    {
        $this->ensureListed($account);
        Gate::authorize('update', $account);

        if ($account->archived_at !== null) {
            $account->update([
                'archived_at' => null,
                'updated_by' => $this->user($request)->id,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Account restored.']);

        return to_route('accounts.show', $account);
    }

    private function ensureListed(Account $account): void
    {
        if ($account->is_imported_history) {
            abort(404);
        }
    }

    private function user(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
