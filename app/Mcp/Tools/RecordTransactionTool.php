<?php

namespace App\Mcp\Tools;

use App\Actions\Transactions\CreateTransaction;
use App\Catalogs\TransactionCatalog;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Money\Decimal;
use App\Money\MoneyConverter;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

abstract class RecordTransactionTool extends Tool
{
    abstract protected function type(): TransactionType;

    /**
     * Handle the tool request.
     */
    public function handle(
        Request $request,
        CreateTransaction $create,
        TransactionCatalog $catalog,
    ): Response|ResponseFactory {
        $user = $request->user();

        if (! $user instanceof User) {
            return Response::error('Authentication required.');
        }

        Gate::authorize('create', Transaction::class);

        $validated = $request->validate([
            'amount' => ['required', 'regex:/^-?\d{1,13}(\.\d{1,2})?$/'],
            'account_id' => ['required', 'integer'],
            'category_id' => ['required', 'integer'],
        ], [
            'amount.required' => 'Enter an amount.',
            'amount.regex' => 'Enter an amount with up to two decimal places.',
            'account_id.required' => 'Choose an account.',
            'account_id.integer' => 'Choose an account.',
            'category_id.required' => 'Choose a category.',
            'category_id.integer' => 'Choose a category.',
        ]);

        $amount = Decimal::of($validated['amount']);

        if ($amount->isNegative()) {
            throw ValidationException::withMessages([
                'amount' => $this->type() === TransactionType::Income
                    ? 'Enter an income amount greater than zero.'
                    : 'Enter an amount greater than zero.',
            ]);
        }

        if ($amount->isZero()) {
            throw ValidationException::withMessages([
                'amount' => 'Enter an amount other than zero.',
            ]);
        }

        $account = Account::query()
            ->whereBelongsTo($user->workspace)
            ->find($validated['account_id']);

        if (! $account instanceof Account) {
            throw ValidationException::withMessages([
                'account_id' => 'Choose an account.',
            ]);
        }

        $category = Category::query()
            ->whereBelongsTo($user->workspace)
            ->find($validated['category_id']);

        if (! $category instanceof Category) {
            throw ValidationException::withMessages([
                'category_id' => 'Choose a category.',
            ]);
        }

        $transaction = $create->handle($account, $user, [
            'type' => $this->type(),
            'amount' => $amount->toString(MoneyConverter::SCALE),
            'currency' => $account->currency,
            'booked_on' => now()->toDateString(),
            'category' => $category,
        ]);

        return Response::structured($catalog->detail($transaction, $user));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $kind = $this->type()->value;

        return [
            'amount' => $schema->string()
                ->description('Amount in the account currency, greater than zero, with up to two decimal places and no thousands separator.')
                ->pattern('^\d{1,13}(\.\d{1,2})?$')
                ->required(),
            'account_id' => $schema->integer()
                ->description('Id of an account in this workspace.')
                ->required(),
            'category_id' => $schema->integer()
                ->description("Id of an {$kind} category in this workspace.")
                ->required(),
        ];
    }
}
