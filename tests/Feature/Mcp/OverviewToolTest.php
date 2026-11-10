<?php

use App\Actions\Transactions\CreateTransaction;
use App\Catalogs\OverviewCatalog;
use App\Enums\TransactionType;
use App\Mcp\Servers\KasicaServer;
use App\Mcp\Tools\GetOverviewTool;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use App\Models\Workspace;
use App\Support\CalendarMonth;
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

test('guests cannot call the mcp server', function () {
    postMcp('tools/list')
        ->assertUnauthorized()
        ->assertHeader('WWW-Authenticate');
});

test('unverified members cannot call the mcp server', function () {
    $user = User::factory()->unverified()->create();

    Passport::actingAs($user, ['mcp:use']);

    postMcp('tools/call', [
        'name' => 'get_overview',
        'arguments' => [],
    ])->assertForbidden();
});

test('tokens without the mcp scope cannot call the mcp server', function () {
    Passport::actingAs(User::factory()->create(), []);

    postMcp('tools/call', [
        'name' => 'get_overview',
        'arguments' => [],
    ])->assertForbidden();
});

test('oauth clients cannot register an outside redirect', function () {
    $this->postJson('/oauth/register', [
        'client_name' => 'Elsewhere',
        'redirect_uris' => ['https://evil.example/callback'],
    ])->assertBadRequest()
        ->assertJsonPath('error', 'invalid_redirect_uri');
});

test('oauth clients can register a cursor redirect', function () {
    $this->postJson('/oauth/register', [
        'client_name' => 'Cursor',
        'redirect_uris' => ['cursor://anysphere.cursor-mcp/callback'],
    ])->assertCreated()
        ->assertJsonPath('scope', 'mcp:use');
});

test('get_overview requires an authenticated member', function () {
    KasicaServer::tool(GetOverviewTool::class)
        ->assertHasErrors(['Authentication required.']);
});

test('get_overview returns the dashboard overview for the member workspace', function () {
    $this->travelTo('2026-10-20');
    $user = User::factory()->create();
    $account = Account::factory()->create(['name' => 'Wallet']);
    $groceries = Category::query()->where('name', 'Groceries')->firstOrFail();

    app(CreateTransaction::class)->handle($account, $user, [
        'type' => TransactionType::Expense,
        'amount' => '12.50',
        'currency' => 'RSD',
        'booked_on' => '2026-10-20',
        'category' => $groceries,
        'note' => 'Market',
    ]);

    $page = app(OverviewCatalog::class)->page($user, CalendarMonth::fromValue('2026-10'));

    KasicaServer::actingAs($user)->tools()
        ->assertRegistered(GetOverviewTool::class);

    KasicaServer::actingAs($user)
        ->tool(GetOverviewTool::class, ['month' => '2026-10'])
        ->assertOk()
        ->assertStructuredContent($page);

    KasicaServer::actingAs($user)
        ->tool(GetOverviewTool::class)
        ->assertOk()
        ->assertStructuredContent($page);

    $other = Workspace::factory()->create(['name' => 'Other']);
    $outsider = User::factory()->create(['workspace_id' => $other->id]);

    KasicaServer::actingAs($outsider)
        ->tool(GetOverviewTool::class, ['month' => '2026-10'])
        ->assertOk()
        ->assertStructuredContent(fn ($content) => $content
            ->where('totals.spent.base_amount', '0.00')
            ->etc());

    Passport::actingAs($user, ['mcp:use']);

    postMcp('tools/call', [
        'name' => 'get_overview',
        'arguments' => ['month' => '2026-10'],
    ])->assertOk()
        ->assertJsonPath('result.structuredContent.totals.spent.base_amount', '12.50');
});

test('get_overview rejects a month that is not YYYY-MM', function () {
    $user = User::factory()->create();

    KasicaServer::actingAs($user)
        ->tool(GetOverviewTool::class, ['month' => '2026-13'])
        ->assertHasErrors();
});

/**
 * @param  array<string, mixed>  $params
 */
function postMcp(string $method, array $params = []): TestResponse
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
