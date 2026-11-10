<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreateExpenseTool;
use App\Mcp\Tools\CreateIncomeTool;
use App\Mcp\Tools\GetOverviewTool;
use Laravel\Mcp\Enums\CacheScope;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Cacheable;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;

#[Name('Kasica Prasica')]
#[Version('1.0.0')]
#[Cacheable(ttlMs: 0, scope: CacheScope::Private)]
#[Instructions('Private household budget for the authenticated member. get_overview returns that member\'s workspace month overview: income, spending, category budgets, savings rate, balance, and the daily balance flow. create_expense and create_income record a transaction for today in that workspace. Each takes an amount, an account id, and a category id of the matching kind. The signed-in member is the person. Expense category ids are on the overview rows.')]
class KasicaServer extends Server
{
    /**
     * The tools registered with this MCP server.
     *
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        GetOverviewTool::class,
        CreateExpenseTool::class,
        CreateIncomeTool::class,
    ];
}
