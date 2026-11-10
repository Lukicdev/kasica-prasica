<?php

use App\Catalogs\TransactionCatalog;
use App\Enums\TransactionType;
use App\Mcp\Servers\KasicaServer;
use App\Mcp\Tools\CreateExpenseTool;
use App\Mcp\Tools\CreateIncomeTool;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use App\Money\Decimal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
    $directory = storage_path('framework/testing/passport');

    File::ensureDirectoryExists($directory);
    Passport::loadKeysFrom($directory);

    if (is_file(Passport::keyPath('oauth-private.key')) && is_file(Passport::keyPath('oauth-public.key'))) {
        return;
    }

    putenv('RANDFILE='.$directory.'/openssl.rnd');
    putenv('HOME='.$directory);

    Artisan::call('passport:keys', [
        '--force' => true,
        '--length' => 2048,
    ]);
});

test('create_expense and create_income require an authenticated member', function () {
    KasicaServer::tool(CreateExpenseTool::class, [
        'amount' => '10',
        'account_id' => 1,
        'category_id' => 1,
    ])->assertHasErrors(['Authentication required.']);

    KasicaServer::tool(CreateIncomeTool::class, [
        'amount' => '10',
        'account_id' => 1,
        'category_id' => 1,
    ])->assertHasErrors(['Authentication required.']);
});

test('create_expense records an expense for today and returns the new balance', function () {
    $this->travelTo('2026-10-20');
    $user = User::factory()->create(['name' => 'Ada']);
    $account = Account::factory()->create([
        'name' => 'Household cash',
        'opening_balance' => '1000.00',
    ]);
    $category = Category::factory()->create(['name' => 'Corner shop']);

    KasicaServer::actingAs($user)->tools()
        ->assertRegistered(CreateExpenseTool::class);

    KasicaServer::actingAs($user)
        ->tool(CreateExpenseTool::class, [
            'amount' => '42.50',
            'account_id' => $account->id,
            'category_id' => $category->id,
        ])
        ->assertOk()
        ->assertStructuredContent(app(TransactionCatalog::class)->detail(
            Transaction::query()->firstOrFail(),
            $user,
        ));

    $transaction = Transaction::query()->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->type)->toBe(TransactionType::Expense)
        ->and(Decimal::of((string) $transaction->amount)->toString(2))->toBe('42.50')
        ->and($transaction->currency)->toBe('RSD')
        ->and($transaction->booked_on->toDateString())->toBe('2026-10-20')
        ->and($transaction->account_id)->toBe($account->id)
        ->and($transaction->category_id)->toBe($category->id)
        ->and($transaction->person_id)->toBe($user->id)
        ->and($transaction->note)->toBeNull()
        ->and($transaction->repeats)->toBeFalse()
        ->and($transaction->created_by)->toBe($user->id);

    Passport::actingAs($user, ['mcp:use']);

    postRecordMcp('tools/call', [
        'name' => 'create_expense',
        'arguments' => [
            'amount' => '10',
            'account_id' => $account->id,
            'category_id' => $category->id,
        ],
    ])->assertOk()
        ->assertJsonPath('result.structuredContent.balance.amount', '947.50')
        ->assertJsonPath('result.structuredContent.type_label', 'Expense')
        ->assertJsonPath('result.structuredContent.date', '2026-10-20');
});

test('create_income records income for today on the account', function () {
    $this->travelTo('2026-10-20');
    $user = User::factory()->create();
    $account = Account::factory()->inCurrency('EUR')->create(['opening_balance' => '100.00']);
    $category = Category::factory()->income()->create();

    KasicaServer::actingAs($user)->tools()
        ->assertRegistered(CreateIncomeTool::class);

    KasicaServer::actingAs($user)
        ->tool(CreateIncomeTool::class, [
            'amount' => '50',
            'account_id' => $account->id,
            'category_id' => $category->id,
        ])
        ->assertOk()
        ->assertStructuredContent(fn ($content) => $content
            ->where('type_label', 'Income')
            ->where('date', '2026-10-20')
            ->where('note', null)
            ->where('person', $user->name)
            ->etc());

    $transaction = Transaction::query()->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->type)->toBe(TransactionType::Income)
        ->and($transaction->currency)->toBe('EUR')
        ->and($transaction->person_id)->toBe($user->id)
        ->and($transaction->repeats)->toBeFalse();
});

test('a submitted date, person, note, or refund is ignored', function () {
    $this->travelTo('2026-10-20');
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $category = Category::factory()->create();
    $other = User::factory()->create([
        'workspace_id' => Workspace::factory()->create()->id,
    ]);

    KasicaServer::actingAs($user)
        ->tool(CreateExpenseTool::class, [
            'amount' => '10',
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'income',
            'currency' => 'EUR',
            'booked_on' => '2020-01-01',
            'person_id' => $other->id,
            'note' => 'Ignored',
            'refund' => true,
            'recurring' => true,
        ])
        ->assertOk();

    $transaction = Transaction::query()->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction->type)->toBe(TransactionType::Expense)
        ->and(Decimal::of((string) $transaction->amount)->toString(2))->toBe('10.00')
        ->and($transaction->currency)->toBe('RSD')
        ->and($transaction->booked_on->toDateString())->toBe('2026-10-20')
        ->and($transaction->person_id)->toBe($user->id)
        ->and($transaction->note)->toBeNull()
        ->and($transaction->repeats)->toBeFalse()
        ->and($transaction->isRefund())->toBeFalse();
});

test('create_expense rejects a category of the wrong kind and an account outside the workspace', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $income = Category::factory()->income()->create();
    $foreign = Account::factory()->create([
        'workspace_id' => Workspace::factory()->create()->id,
    ]);
    $expense = Category::factory()->create();

    KasicaServer::actingAs($user)
        ->tool(CreateExpenseTool::class, [
            'amount' => '10',
            'account_id' => $account->id,
            'category_id' => $income->id,
        ])
        ->assertHasErrors(['Choose a category of the same kind.']);

    KasicaServer::actingAs($user)
        ->tool(CreateIncomeTool::class, [
            'amount' => '-5',
            'account_id' => $account->id,
            'category_id' => $income->id,
        ])
        ->assertHasErrors(['Enter an income amount greater than zero.']);

    KasicaServer::actingAs($user)
        ->tool(CreateExpenseTool::class, [
            'amount' => '10',
            'account_id' => $foreign->id,
            'category_id' => $expense->id,
        ])
        ->assertHasErrors(['Choose an account.']);

    expect(Transaction::query()->count())->toBe(0);
});

test('create_expense rejects an amount that is not a positive decimal', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create();
    $category = Category::factory()->create();

    KasicaServer::actingAs($user)
        ->tool(CreateExpenseTool::class, [
            'amount' => '12.345',
            'account_id' => $account->id,
            'category_id' => $category->id,
        ])
        ->assertHasErrors(['Enter an amount with up to two decimal places.']);

    KasicaServer::actingAs($user)
        ->tool(CreateExpenseTool::class, [
            'amount' => '-5',
            'account_id' => $account->id,
            'category_id' => $category->id,
        ])
        ->assertHasErrors(['Enter an amount greater than zero.']);

    expect(Transaction::query()->count())->toBe(0);
});

/**
 * @param  array<string, mixed>  $params
 */
function postRecordMcp(string $method, array $params = []): TestResponse
{
    $headers = [
        'MCP-Protocol-Version' => '2025-11-25',
        'Mcp-Method' => $method,
    ];

    if (isset($params['name']) && is_string($params['name'])) {
        $headers['Mcp-Name'] = $params['name'];
    }

    return test()->postJson('/mcp', [
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => $method,
        'params' => $params,
    ], $headers);
}
